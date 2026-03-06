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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/{definitionId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $detail = $this->queryBus->ask(new GetProcessDefinitionQuery($definitionId));

        return new JsonResponse($detail);
    }

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

    #[Route('/{definitionId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_DELETE');

        $this->commandBus->dispatch(new DeleteProcessDefinitionCommand($definitionId));

        return new JsonResponse(['message' => 'Process definition deleted.']);
    }

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

    #[Route('/{definitionId}/start-form', name: 'start_form', methods: ['GET'])]
    public function startForm(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        /** @var array{fields: list<array<string, mixed>>} $schema */
        $schema = $this->queryBus->ask(new GetStartFormSchemaQuery($definitionId));

        return new JsonResponse($schema);
    }

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

    #[Route('/{definitionId}/versions', name: 'versions', methods: ['GET'])]
    public function versions(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_VIEW');

        $versions = $this->queryBus->ask(new ListVersionsQuery($definitionId));

        return new JsonResponse($versions);
    }

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

    #[Route('/{definitionId}/access', name: 'get_access', methods: ['GET'])]
    public function getAccess(string $organizationId, string $definitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $accessRules = $this->queryBus->ask(new GetProcessDefinitionAccessQuery($definitionId));

        return new JsonResponse($accessRules);
    }

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
