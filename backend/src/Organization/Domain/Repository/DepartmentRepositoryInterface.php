<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;

interface DepartmentRepositoryInterface
{
    public function save(Department $department): void;

    public function remove(Department $department): void;

    public function findById(DepartmentId $id): ?Department;

    public function existsByCode(DepartmentCode $code, OrganizationId $organizationId): bool;

    /**
     * @return list<Department>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;

    /**
     * @return list<Department>
     */
    public function findByParentId(OrganizationId $organizationId, ?DepartmentId $parentId): array;

    /**
     * @return list<Department>
     */
    public function findDescendants(DepartmentId $departmentId): array;
}
