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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/process-instances', name: 'api_v1_process_instances_')]
final readonly class ProcessInstanceController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[Route('', name: 'start', methods: ['POST'])]
    public function start(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_CREATE');
        $data = $this->decodeJson($request);

        /** @var array<string, mixed> $variables */
        $variables = $data['variables'] ?? [];
        $id = ProcessInstanceId::generate()->value();

        $this->commandBus->dispatch(new StartProcessCommand(
            id: $id,
            processDefinitionId: (string) ($data['process_definition_id'] ?? ''),
            organizationId: $organizationId,
            startedBy: $this->currentUserProvider->getUserId(),
            variables: $variables,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

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

    #[Route('/{instanceId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $instanceId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $instance = $this->queryBus->ask(new GetProcessInstanceQuery($instanceId));

        return new JsonResponse($instance);
    }

    #[Route('/{instanceId}/history', name: 'history', methods: ['GET'])]
    public function history(string $organizationId, string $instanceId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $events = $this->queryBus->ask(new GetProcessInstanceHistoryQuery($instanceId));

        return new JsonResponse($events);
    }

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
