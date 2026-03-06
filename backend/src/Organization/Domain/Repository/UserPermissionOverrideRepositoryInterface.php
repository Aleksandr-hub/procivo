<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\UserPermissionOverride;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;

interface UserPermissionOverrideRepositoryInterface
{
    public function save(UserPermissionOverride $override): void;

    public function remove(UserPermissionOverride $override): void;

    /**
     * @return list<UserPermissionOverride>
     */
    public function findByEmployeeId(EmployeeId $employeeId): array;

    public function findByEmployeeIdResourceAction(
        EmployeeId $employeeId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?UserPermissionOverride;
}
