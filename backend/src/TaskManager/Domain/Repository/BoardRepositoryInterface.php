<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\TaskManager\Domain\Entity\Board;
use App\TaskManager\Domain\ValueObject\BoardId;

interface BoardRepositoryInterface
{
    public function save(Board $board): void;

    public function remove(Board $board): void;

    public function findById(BoardId $id): ?Board;

    /**
     * @return list<Board>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;
}
