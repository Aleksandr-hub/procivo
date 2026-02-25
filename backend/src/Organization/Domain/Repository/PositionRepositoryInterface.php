<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Position;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;

interface PositionRepositoryInterface
{
    public function save(Position $position): void;

    public function remove(Position $position): void;

    public function findById(PositionId $id): ?Position;

    /**
     * @return list<Position>
     */
    public function findByDepartmentId(DepartmentId $departmentId): array;

    /**
     * @return list<Position>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;
}
