<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListBoards;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\TaskManager\Application\DTO\BoardColumnDTO;
use App\TaskManager\Application\DTO\BoardDTO;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListBoardsHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
        private BoardColumnRepositoryInterface $columnRepository,
    ) {
    }

    /**
     * @return list<BoardDTO>
     */
    public function __invoke(ListBoardsQuery $query): array
    {
        $boards = $this->boardRepository->findByOrganizationId(
            OrganizationId::fromString($query->organizationId),
        );

        return array_map(function ($board) {
            $columns = $this->columnRepository->findByBoardId($board->id());

            $columnDtos = array_map(
                static fn ($col) => BoardColumnDTO::fromEntity($col),
                $columns,
            );

            usort($columnDtos, static fn ($a, $b) => $a->position <=> $b->position);

            return BoardDTO::fromEntity($board, $columnDtos);
        }, $boards);
    }
}
