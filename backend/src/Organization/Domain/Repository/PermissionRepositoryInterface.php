<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Permission;
use App\Organization\Domain\ValueObject\PermissionId;
use App\Organization\Domain\ValueObject\RoleId;

interface PermissionRepositoryInterface
{
    public function save(Permission $permission): void;

    public function findById(PermissionId $id): ?Permission;

    /**
     * @return list<Permission>
     */
    public function findByRoleId(RoleId $roleId): array;

    /**
     * @param list<string> $roleIds
     *
     * @return list<Permission>
     */
    public function findByRoleIds(array $roleIds): array;

    public function delete(Permission $permission): void;

    public function deleteByRoleId(RoleId $roleId): void;

    public function findByRoleIdResourceAndAction(RoleId $roleId, string $resource, string $action): ?Permission;
}
