<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security\Service;

use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;

interface PermissionResolverInterface
{
    public function hasPermission(
        string $userId,
        string $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
    ): bool;

    public function resolveScope(
        string $userId,
        string $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?PermissionScope;

    /**
     * @return list<string>
     */
    public function resolveVisibleEmployeeIds(
        string $userId,
        string $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
    ): array;

    /**
     * Returns merged effective permissions for a user in an organization.
     *
     * Merges role permissions, department permissions, and user overrides
     * using the hierarchy: User Override > Role > Department.
     *
     * @return list<array{resource: string, action: string, scope: string}>
     */
    public function resolveEffectivePermissions(string $userId, string $organizationId): array;
}
