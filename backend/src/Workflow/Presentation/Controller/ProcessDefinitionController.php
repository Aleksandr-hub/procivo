<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Controller;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Workflow\Application\Command\CreateProcessDefinition\CreateProcessDefinitionCommand;
use App\Workflow\Application\Command\DeleteProcessDefinition\DeleteProcessDefinitionCommand;
use App\Workflow\Application\Command\MigrateProcessInstances\MigrateProcessInstancesCommand;
use App\Workflow\Application\Command\PublishProcessDefinition\PublishProcessDefinitionCommand;
use App\Workflow\Application\Command\RevertProcessDefinitionToDraft\RevertProcessDefinitionToDraftCommand;
use App\Workflow\Application\Command\SetProcessDefinitionAccess\SetProcessDefinitionAccessCommand;
use App\Workflow\Application\Command\UpdateProcessDefinition\UpdateProcessDefinitionCommand;
use App\Workflow\Application\Query\GetProcessDefinition\GetProcessDefinitionQuery;
use App\Workflow\Application\Query\GetProcessDefinitionAccess\GetProcessDefinitionAccessQuery;
use App\Workflow\Application\Query\GetStartFormSchema\GetStartFormSchemaQuery;
use App\Workflow\Application\Query\ListProcessDefinitions\ListProcessDefinitionsQuery;
use App\Workflow\Application\Query\ListVersions\ListVersionsQuery;
use App\Workflow\Domain\ValueObject\AccessType;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Presentation\Security\ProcessDefinitionAccessChecker;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Workflow')]
#[Route('/api/v1/organizations/{organizationId}/process-definitions', name: 'api_v1_process_definitions_')]
final readonly class ProcessDefinitionController
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
        summary: 'Create a process definition',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Definition created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_CREATE');
        $data = $this->decodeJson($request);

        $id = ProcessDefinitionId::generate()->value();

        $this->commandBus->dispatch(new CreateProcessDefinitionCommand(
            id: $id,
            organizationId: $organizationId,
            name: $data['name'] ?? '',
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
            createdBy: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Get(
        summary: 'List process definitions',
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
        ],
    )]
    #[OA\Response(response: 200, description: 'Definition list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\Workflow\Application\DTO\ProcessDefinitionDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $status = $request->query->getString('status');

        /** @var list<\App\Workflow\Application\DTO\ProcessDefinitionDTO> $definitions */
        $definitions = $this->queryBus->ask(new ListProcessDefinitionsQuery(
            organizationId: $organizationId,
            status: '' !== $status ? $status : null,
        ));

        // Filter by per-definition view access (null = owner bypass, show all)
        $accessibleIds = $this->accessChecker->getAccessibleDefinitionIds($organizationId, AccessType::View);
        if (null !== $accessibleIds) {
            $accessibleIdSet = array_flip($accessibleIds);
            $definitions = array_values(array_filter(
                $definitions,
                static fn ($d) => isset($accessibleIdSet[$d->id]),
            ));
        }

        return new JsonResponse($definitions);
    }

    #[OA\Get(summary: 'Get process definition with full graph')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Definition detail with nodes and transitions', content: new OA\JsonContent(ref: new Model(type: \App\Workflow\Application\DTO\ProcessDefinitionDetailDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Definition not found')]
    #[Route('/{definitionId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $detail = $this->queryBus->ask(new GetProcessDefinitionQuery($definitionId));

        return new JsonResponse($detail);
    }

    #[OA\Put(
        summary: 'Update process definition',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Definition updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $definitionId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateProcessDefinitionCommand(
            processDefinitionId: $definitionId,
            name: $data['name'] ?? '',
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
        ));

        return new JsonResponse(['message' => 'Process definition updated.']);
    }

    #[OA\Delete(summary: 'Delete process definition')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Definition deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_DELETE');

        $this->commandBus->dispatch(new DeleteProcessDefinitionCommand($definitionId));

        return new JsonResponse(['message' => 'Process definition deleted.']);
    }

    #[OA\Post(summary: 'Publish process definition (creates a new version)')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Definition published', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/publish', name: 'publish', methods: ['POST'])]
    public function publish(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $this->commandBus->dispatch(new PublishProcessDefinitionCommand(
            processDefinitionId: $definitionId,
            publishedBy: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Process definition published.']);
    }

    #[OA\Get(summary: 'Get start form schema for a process definition')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(
        response: 200,
        description: 'Start form schema',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'fields', type: 'array', items: new OA\Items(type: 'object')),
        ]),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/start-form', name: 'start_form', methods: ['GET'])]
    public function startForm(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        /** @var array{fields: list<array<string, mixed>>} $schema */
        $schema = $this->queryBus->ask(new GetStartFormSchemaQuery($definitionId));

        return new JsonResponse($schema);
    }

    #[OA\Post(summary: 'Revert published definition back to draft')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Reverted to draft', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/revert-to-draft', name: 'revert_to_draft', methods: ['POST'])]
    public function revertToDraft(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $this->commandBus->dispatch(new RevertProcessDefinitionToDraftCommand(
            processDefinitionId: $definitionId,
            revertedBy: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Process definition reverted to draft.']);
    }

    #[OA\Get(summary: 'List published versions of a process definition')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Version list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\Workflow\Application\DTO\ProcessDefinitionVersionDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/versions', name: 'versions', methods: ['GET'])]
    public function versions(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $versions = $this->queryBus->ask(new ListVersionsQuery($definitionId));

        return new JsonResponse($versions);
    }

    #[OA\Post(summary: 'Migrate running instances to a target version')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'versionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Instances migrated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/versions/{versionId}/migrate', name: 'migrate_instances', methods: ['POST'])]
    public function migrateInstances(string $organizationId, string $definitionId, string $versionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $this->commandBus->dispatch(new MigrateProcessInstancesCommand(
            processDefinitionId: $definitionId,
            targetVersionId: $versionId,
            migratedBy: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Running instances migrated to target version.']);
    }

    #[OA\Get(summary: 'Get access control rules for a process definition')]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Access rules', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: \App\Workflow\Application\DTO\ProcessDefinitionAccessDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/access', name: 'get_access', methods: ['GET'])]
    public function getAccess(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $accessRules = $this->queryBus->ask(new GetProcessDefinitionAccessQuery($definitionId));

        return new JsonResponse($accessRules);
    }

    #[OA\Put(
        summary: 'Set access control rules for a process definition',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['accessType', 'entries'],
                properties: [
                    new OA\Property(property: 'accessType', type: 'string', enum: ['starter', 'viewer']),
                    new OA\Property(
                        property: 'entries',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'departmentId', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'roleId', type: 'string', format: 'uuid', nullable: true),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'definitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Access rules updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{definitionId}/access', name: 'set_access', methods: ['PUT'])]
    public function setAccess(string $organizationId, string $definitionId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        /** @var string $accessType */
        $accessType = $data['accessType'] ?? '';
        /** @var list<array{departmentId?: ?string, roleId?: ?string}> $rawEntries */
        $rawEntries = $data['entries'] ?? [];

        $entries = array_map(
            static fn (array $entry): array => [
                'departmentId' => $entry['departmentId'] ?? null,
                'roleId' => $entry['roleId'] ?? null,
            ],
            $rawEntries,
        );

        $this->commandBus->dispatch(new SetProcessDefinitionAccessCommand(
            processDefinitionId: $definitionId,
            organizationId: $organizationId,
            accessType: $accessType,
            entries: $entries,
            actorId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Access rules updated.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
