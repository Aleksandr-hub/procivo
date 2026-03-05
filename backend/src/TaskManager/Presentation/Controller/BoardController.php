<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Command\AddColumn\AddColumnCommand;
use App\TaskManager\Application\Command\CreateBoard\CreateBoardCommand;
use App\TaskManager\Application\Command\CreateProcessBoard\CreateProcessBoardCommand;
use App\TaskManager\Application\Command\DeleteBoard\DeleteBoardCommand;
use App\TaskManager\Application\Command\DeleteColumn\DeleteColumnCommand;
use App\TaskManager\Application\Command\UpdateBoard\UpdateBoardCommand;
use App\TaskManager\Application\Command\UpdateColumn\UpdateColumnCommand;
use App\TaskManager\Application\Query\GetBoard\GetBoardQuery;
use App\TaskManager\Application\Query\GetProcessBoardData\GetProcessBoardDataQuery;
use App\TaskManager\Application\Query\ListBoards\ListBoardsQuery;
use App\TaskManager\Domain\ValueObject\BoardId;
use App\TaskManager\Domain\ValueObject\ColumnId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/boards', name: 'api_v1_boards_')]
final readonly class BoardController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_CREATE');
        $data = $this->decodeJson($request);

        $id = BoardId::generate()->value();

        $this->commandBus->dispatch(new CreateBoardCommand(
            id: $id,
            organizationId: $organizationId,
            name: $data['name'] ?? '',
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('/process', name: 'create_process_board', methods: ['POST'])]
    public function createProcessBoard(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_CREATE');
        $data = $this->decodeJson($request);

        $id = BoardId::generate()->value();

        $this->commandBus->dispatch(new CreateProcessBoardCommand(
            id: $id,
            organizationId: $organizationId,
            name: $data['name'] ?? '',
            processDefinitionId: isset($data['process_definition_id']) && \is_string($data['process_definition_id'])
                ? $data['process_definition_id']
                : '',
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $boards = $this->queryBus->ask(new ListBoardsQuery($organizationId));

        return new JsonResponse($boards);
    }

    #[Route('/{boardId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $boardId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $board = $this->queryBus->ask(new GetBoardQuery($boardId));

        return new JsonResponse($board);
    }

    #[Route('/{boardId}/process-data', name: 'process_data', methods: ['GET'])]
    public function processData(string $organizationId, string $boardId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $data = $this->queryBus->ask(new GetProcessBoardDataQuery($boardId, $organizationId));

        return new JsonResponse($data);
    }

    #[Route('/{boardId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $boardId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateBoardCommand(
            boardId: $boardId,
            name: $data['name'] ?? '',
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
        ));

        return new JsonResponse(['message' => 'Board updated.']);
    }

    #[Route('/{boardId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $boardId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_DELETE');

        $this->commandBus->dispatch(new DeleteBoardCommand($boardId));

        return new JsonResponse(['message' => 'Board deleted.']);
    }

    #[Route('/{boardId}/columns', name: 'add_column', methods: ['POST'])]
    public function addColumn(string $organizationId, string $boardId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_CREATE');
        $data = $this->decodeJson($request);

        $id = ColumnId::generate()->value();

        $this->commandBus->dispatch(new AddColumnCommand(
            id: $id,
            boardId: $boardId,
            name: $data['name'] ?? '',
            statusMapping: isset($data['status_mapping']) && \is_string($data['status_mapping']) ? $data['status_mapping'] : null,
            wipLimit: isset($data['wip_limit']) && is_numeric($data['wip_limit']) ? (int) $data['wip_limit'] : null,
            color: isset($data['color']) && \is_string($data['color']) ? $data['color'] : null,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('/{boardId}/columns/{columnId}', name: 'update_column', methods: ['PUT'])]
    public function updateColumn(string $organizationId, string $boardId, string $columnId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateColumnCommand(
            columnId: $columnId,
            name: $data['name'] ?? '',
            position: isset($data['position']) && is_numeric($data['position']) ? (int) $data['position'] : 0,
            statusMapping: isset($data['status_mapping']) && \is_string($data['status_mapping']) ? $data['status_mapping'] : null,
            wipLimit: isset($data['wip_limit']) && is_numeric($data['wip_limit']) ? (int) $data['wip_limit'] : null,
            color: isset($data['color']) && \is_string($data['color']) ? $data['color'] : null,
        ));

        return new JsonResponse(['message' => 'Column updated.']);
    }

    #[Route('/{boardId}/columns/{columnId}', name: 'delete_column', methods: ['DELETE'])]
    public function deleteColumn(string $organizationId, string $boardId, string $columnId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_DELETE');

        $this->commandBus->dispatch(new DeleteColumnCommand($columnId));

        return new JsonResponse(['message' => 'Column deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
