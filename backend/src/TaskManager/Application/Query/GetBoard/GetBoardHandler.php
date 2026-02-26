<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetBoard;

use App\TaskManager\Application\DTO\BoardColumnDTO;
use App\TaskManager\Application\DTO\BoardDTO;
use App\TaskManager\Domain\Exception\BoardNotFoundException;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetBoardHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
        private BoardColumnRepositoryInterface $columnRepository,
    ) {
    }

    public function __invoke(GetBoardQuery $query): BoardDTO
    {
        $boardId = BoardId::fromString($query->boardId);
        $board = $this->boardRepository->findById($boardId);

        if (null === $board) {
            throw BoardNotFoundException::withId($query->boardId);
        }

        $columns = $this->columnRepository->findByBoardId($boardId);
        $columnDtos = array_map(
            static fn ($col) => BoardColumnDTO::fromEntity($col),
            $columns,
        );

        usort($columnDtos, static fn ($a, $b) => $a->position <=> $b->position);

        return BoardDTO::fromEntity($board, $columnDtos);
    }
}
