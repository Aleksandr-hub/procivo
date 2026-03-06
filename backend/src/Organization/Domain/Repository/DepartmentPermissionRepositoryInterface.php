<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\DepartmentPermission;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;

interface DepartmentPermissionRepositoryInterface
{
    public function save(DepartmentPermission $permission): void;

    public function remove(DepartmentPermission $permission): void;

    /**
     * @return list<DepartmentPermission>
     */
    public function findByDepartmentId(DepartmentId $departmentId): array;

    /**
     * @param list<string> $departmentIds
     *
     * @return list<DepartmentPermission>
     */
    public function findByDepartmentIds(array $departmentIds): array;

    public function findByDepartmentIdResourceAction(
        DepartmentId $departmentId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?DepartmentPermission;
}
