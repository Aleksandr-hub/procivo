<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Command\AssignLabel\AssignLabelCommand;
use App\TaskManager\Application\Command\CreateLabel\CreateLabelCommand;
use App\TaskManager\Application\Command\DeleteLabel\DeleteLabelCommand;
use App\TaskManager\Application\Command\RemoveLabel\RemoveLabelCommand;
use App\TaskManager\Application\Command\UpdateLabel\UpdateLabelCommand;
use App\TaskManager\Application\Query\GetTaskLabels\GetTaskLabelsQuery;
use App\TaskManager\Application\Query\ListLabels\ListLabelsQuery;
use App\TaskManager\Domain\ValueObject\LabelId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}', name: 'api_v1_labels_')]
final readonly class LabelController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[Route('/labels', name: 'list', methods: ['GET'])]
    public function list(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $labels = $this->queryBus->ask(new ListLabelsQuery($organizationId));

        return new JsonResponse($labels);
    }

    #[Route('/labels', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_CREATE');
        $data = $this->decodeJson($request);

        $id = LabelId::generate()->value();

        $this->commandBus->dispatch(new CreateLabelCommand(
            id: $id,
            organizationId: $organizationId,
            name: $data['name'] ?? '',
            color: $data['color'] ?? '#6366f1',
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('/labels/{labelId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $labelId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateLabelCommand(
            labelId: $labelId,
            name: $data['name'] ?? '',
            color: $data['color'] ?? '#6366f1',
        ));

        return new JsonResponse(['message' => 'Label updated.']);
    }

    #[Route('/labels/{labelId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $labelId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_DELETE');

        $this->commandBus->dispatch(new DeleteLabelCommand($labelId));

        return new JsonResponse(['message' => 'Label deleted.']);
    }

    #[Route('/tasks/{taskId}/labels', name: 'task_labels', methods: ['GET'])]
    public function taskLabels(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $labels = $this->queryBus->ask(new GetTaskLabelsQuery($taskId));

        return new JsonResponse($labels);
    }

    #[Route('/tasks/{taskId}/labels/{labelId}', name: 'assign', methods: ['POST'])]
    public function assign(string $organizationId, string $taskId, string $labelId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');

        $this->commandBus->dispatch(new AssignLabelCommand($taskId, $labelId));

        return new JsonResponse(['message' => 'Label assigned.'], Response::HTTP_CREATED);
    }

    #[Route('/tasks/{taskId}/labels/{labelId}', name: 'remove', methods: ['DELETE'])]
    public function remove(string $organizationId, string $taskId, string $labelId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');

        $this->commandBus->dispatch(new RemoveLabelCommand($taskId, $labelId));

        return new JsonResponse(['message' => 'Label removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
