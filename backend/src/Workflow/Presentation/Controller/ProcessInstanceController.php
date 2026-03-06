<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Controller;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Workflow\Application\Command\CancelProcess\CancelProcessCommand;
use App\Workflow\Application\Command\StartProcess\StartProcessCommand;
use App\Workflow\Application\Query\GetProcessInstance\GetProcessInstanceQuery;
use App\Workflow\Application\Query\GetProcessInstanceGraph\GetProcessInstanceGraphQuery;
use App\Workflow\Application\Query\GetProcessInstanceHistory\GetProcessInstanceHistoryQuery;
use App\Workflow\Application\Query\ListProcessInstances\ListProcessInstancesQuery;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Presentation\Security\ProcessDefinitionAccessChecker;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Workflow')]
#[Route('/api/v1/organizations/{organizationId}/process-instances', name: 'api_v1_process_instances_')]
final readonly class ProcessInstanceController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private CurrentUserProviderInterface $currentUserProvider,
        private ProcessDefinitionAccessChecker $accessChecker,
    ) {
    }

    #[OA\Post(
        summary: 'Start a new process instance',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['process_definition_id'],
                properties: [
                    new OA\Property(property: 'process_definition_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'variables', type: 'object', description: 'Initial process variables'),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Process started', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'start', methods: ['POST'])]
    public function start(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_CREATE');
        $data = $this->decodeJson($request);

        $processDefinitionId = (string) ($data['process_definition_id'] ?? '');

        // Check per-definition start access
        if (!$this->accessChecker->canStartDefinition($organizationId, $processDefinitionId)) {
            throw new AccessDeniedHttpException('You do not have permission to start this process.');
        }

        /** @var array<string, mixed> $variables */
        $variables = $data['variables'] ?? [];
        $id = ProcessInstanceId::generate()->value();

        $this->commandBus->dispatch(new StartProcessCommand(
            id: $id,
            processDefinitionId: $processDefinitionId,
            organizationId: $organizationId,
            startedBy: $this->currentUserProvider->getUserId(),
            variables: $variables,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: 'List process instances',
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['running', 'completed', 'cancelled'])),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated instance list',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: \App\Workflow\Application\DTO\ProcessInstanceDTO::class))),
            new OA\Property(property: 'total', type: 'integer'),
        ]),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $status = $request->query->getString('status');
        $search = $request->query->getString('search');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $instances = $this->queryBus->ask(new ListProcessInstancesQuery(
            organizationId: $organizationId,
            status: '' !== $status ? $status : null,
            search: '' !== $search ? $search : null,
            page: max(1, $page),
            limit: min(100, max(1, $limit)),
        ));

        return new JsonResponse($instances);
    }

    #[OA\Get(summary: 'Get process instance details')]
    #[OA\Parameter(name: 'instanceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Instance details', content: new OA\JsonContent(ref: new Model(type: \App\Workflow\Application\DTO\ProcessInstanceDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Instance not found')]
    #[Route('/{instanceId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $instanceId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $instance = $this->queryBus->ask(new GetProcessInstanceQuery($instanceId));

        return new JsonResponse($instance);
    }

    #[OA\Get(summary: 'Get process instance event history')]
    #[OA\Parameter(name: 'instanceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Event history', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\Workflow\Application\DTO\ProcessEventDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{instanceId}/history', name: 'history', methods: ['GET'])]
    public function history(string $organizationId, string $instanceId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $events = $this->queryBus->ask(new GetProcessInstanceHistoryQuery($instanceId));

        return new JsonResponse($events);
    }

    #[OA\Get(summary: 'Get process instance execution graph')]
    #[OA\Parameter(name: 'instanceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Execution graph', content: new OA\JsonContent(type: 'object'))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Graph not found')]
    #[Route('/{instanceId}/graph', name: 'graph', methods: ['GET'])]
    public function graph(string $organizationId, string $instanceId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $graph = $this->queryBus->ask(new GetProcessInstanceGraphQuery($instanceId));

        if (null === $graph) {
            return new JsonResponse(['error' => 'Graph not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($graph);
    }

    #[OA\Post(
        summary: 'Cancel a running process instance',
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'reason', type: 'string', nullable: true),
            ]),
        ),
    )]
    #[OA\Parameter(name: 'instanceId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Instance cancelled', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{instanceId}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(string $organizationId, string $instanceId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new CancelProcessCommand(
            processInstanceId: $instanceId,
            cancelledBy: $this->currentUserProvider->getUserId(),
            reason: isset($data['reason']) && \is_string($data['reason']) ? $data['reason'] : null,
        ));

        return new JsonResponse(['message' => 'Process instance cancelled.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
