<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\TaskManager\Domain\Entity\BoardColumn;
use App\TaskManager\Domain\ValueObject\BoardId;
use App\TaskManager\Domain\ValueObject\ColumnId;

interface BoardColumnRepositoryInterface
{
    public function save(BoardColumn $column): void;

    public function remove(BoardColumn $column): void;

    public function findById(ColumnId $id): ?BoardColumn;

    /**
     * @return list<BoardColumn>
     */
    public function findByBoardId(BoardId $boardId): array;

    public function getMaxPosition(BoardId $boardId): int;
}
