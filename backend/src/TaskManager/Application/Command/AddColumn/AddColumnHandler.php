<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AddColumn;

use App\TaskManager\Domain\Entity\BoardColumn;
use App\TaskManager\Domain\Exception\BoardNotFoundException;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use App\TaskManager\Domain\ValueObject\ColumnId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AddColumnHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
        private BoardColumnRepositoryInterface $columnRepository,
    ) {
    }

    public function __invoke(AddColumnCommand $command): void
    {
        $boardId = BoardId::fromString($command->boardId);
        $board = $this->boardRepository->findById($boardId);

        if (null === $board) {
            throw BoardNotFoundException::withId($command->boardId);
        }

        $position = $this->columnRepository->getMaxPosition($boardId) + 1;

        $column = BoardColumn::create(
            id: ColumnId::fromString($command->id),
            boardId: $boardId,
            name: $command->name,
            position: $position,
            statusMapping: $command->statusMapping,
            wipLimit: $command->wipLimit,
            color: $command->color,
        );

        $this->columnRepository->save($column);
    }
}
