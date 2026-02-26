<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateBoard;

use App\TaskManager\Domain\Exception\BoardNotFoundException;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateBoardHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
    ) {
    }

    public function __invoke(UpdateBoardCommand $command): void
    {
        $board = $this->boardRepository->findById(BoardId::fromString($command->boardId));

        if (null === $board) {
            throw BoardNotFoundException::withId($command->boardId);
        }

        $board->update($command->name, $command->description);
        $this->boardRepository->save($board);
    }
}
