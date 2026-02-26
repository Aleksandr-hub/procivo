<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteBoard;

use App\TaskManager\Domain\Exception\BoardNotFoundException;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteBoardHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
        private BoardColumnRepositoryInterface $columnRepository,
    ) {
    }

    public function __invoke(DeleteBoardCommand $command): void
    {
        $boardId = BoardId::fromString($command->boardId);
        $board = $this->boardRepository->findById($boardId);

        if (null === $board) {
            throw BoardNotFoundException::withId($command->boardId);
        }

        foreach ($this->columnRepository->findByBoardId($boardId) as $column) {
            $this->columnRepository->remove($column);
        }

        $this->boardRepository->remove($board);
    }
}
