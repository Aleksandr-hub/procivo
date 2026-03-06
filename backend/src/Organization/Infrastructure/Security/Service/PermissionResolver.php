<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security\Service;

use App\Organization\Domain\Repository\DepartmentPermissionRepositoryInterface;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\Repository\UserPermissionOverrideRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionEffect;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;

final readonly class PermissionResolver implements PermissionResolverInterface
{
    private const array SCOPE_PRIORITY = [
        'organization' => 6,
        'department_tree' => 5,
        'department' => 4,
        'subordinates_tree' => 3,
        'subordinates' => 2,
        'own' => 1,
    ];

    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private PermissionRepositoryInterface $permissionRepository,
        private DepartmentRepositoryInterface $departmentRepository,
        private DepartmentPermissionRepositoryInterface $departmentPermissionRepository,
        private UserPermissionOverrideRepositoryInterface $userOverrideRepository,
    ) {
    }

    public function hasPermission(
        string $userId,
        string $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
    ): bool {
        return null !== $this->resolveScope($userId, $organizationId, $resource, $action);
    }

    public function resolveScope(
        string $userId,
        string $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?PermissionScope {
        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $userId,
            OrganizationId::fromString($organizationId),
        );

        if (null === $employee || !$employee->isActive()) {
            return null;
        }

        // 1. Check user-level override (highest priority)
        $userOverride = $this->userOverrideRepository->findByEmployeeIdResourceAction(
            $employee->id(),
            $resource,
            $action,
        );

        if (null !== $userOverride) {
            if ($userOverride->isDeny()) {
                return null;
            }

            // Allow override — use override's scope
            return $userOverride->scope();
        }

        // 2. Check role-based permissions (existing logic)
        $roleScope = $this->resolveRoleScope($employee->id(), $resource, $action);

        // 3. Check department-level permissions (with hierarchy)
        $departmentScope = $this->resolveDepartmentScope($employee->departmentId(), $resource, $action);

        // Return the widest scope from role + department
        return $this->getWiderScope($roleScope, $departmentScope);
    }

    /**
     * @return list<string>
     */
    public function resolveVisibleEmployeeIds(
        string $userId,
        string $organizationId,
        PermissionResource $resource,
        PermissionAction $action,
    ): array {
        $scope = $this->resolveScope($userId, $organizationId, $resource, $action);
        if (null === $scope) {
            return [];
        }

        $orgId = OrganizationId::fromString($organizationId);
        $employee = $this->employeeRepository->findByUserIdAndOrganizationId($userId, $orgId);
        if (null === $employee) {
            return [];
        }

        return match ($scope) {
            PermissionScope::Own => [$employee->id()->value()],
            PermissionScope::Subordinates => $this->getDirectSubordinateIds($employee->id()),
            PermissionScope::SubordinatesTree => $this->getSubordinateTreeIds($employee->id()),
            PermissionScope::Department => $this->getDepartmentEmployeeIds($employee),
            PermissionScope::DepartmentTree => $this->getDepartmentTreeEmployeeIds($employee),
            PermissionScope::Organization => $this->getOrganizationEmployeeIds($orgId),
        };
    }

    /**
     * @return list<array{resource: string, action: string, scope: string}>
     */
    public function resolveEffectivePermissions(string $userId, string $organizationId): array
    {
        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $userId,
            OrganizationId::fromString($organizationId),
        );

        if (null === $employee || !$employee->isActive()) {
            return [];
        }

        // Collect all user overrides
        $userOverrides = $this->userOverrideRepository->findByEmployeeId($employee->id());
        $overrideMap = [];
        foreach ($userOverrides as $override) {
            $key = $override->resource()->value . ':' . $override->action()->value;
            $overrideMap[$key] = $override;
        }

        // Collect role permissions
        $rolePermissions = $this->getRolePermissions($employee->id());

        // Collect department permissions (with hierarchy)
        $departmentPermissions = $this->getDepartmentPermissionsForEmployee($employee->departmentId());

        // Merge: build effective map of resource:action -> scope
        /** @var array<string, PermissionScope> $effectiveMap */
        $effectiveMap = [];

        // Start with department permissions
        foreach ($departmentPermissions as $dp) {
            $key = $dp->resource()->value . ':' . $dp->action()->value;
            $existing = $effectiveMap[$key] ?? null;
            $effectiveMap[$key] = $this->getWiderScope($existing, $dp->scope()) ?? $dp->scope();
        }

        // Merge role permissions (wider wins)
        foreach ($rolePermissions as $rp) {
            $key = $rp->resource()->value . ':' . $rp->action()->value;
            $existing = $effectiveMap[$key] ?? null;
            $effectiveMap[$key] = $this->getWiderScope($existing, $rp->scope()) ?? $rp->scope();
        }

        // Apply user overrides (highest priority)
        foreach ($overrideMap as $key => $override) {
            if ($override->effect() === PermissionEffect::Deny) {
                unset($effectiveMap[$key]);
            } else {
                $effectiveMap[$key] = $override->scope();
            }
        }

        // Convert to output format
        $result = [];
        foreach ($effectiveMap as $key => $scope) {
            [$resource, $action] = explode(':', $key);
            $result[] = [
                'resource' => $resource,
                'action' => $action,
                'scope' => $scope->value,
            ];
        }

        return $result;
    }

    private function resolveRoleScope(
        EmployeeId $employeeId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?PermissionScope {
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId($employeeId);
        if ([] === $employeeRoles) {
            return null;
        }

        $roleIds = array_map(
            static fn ($er) => $er->roleId()->value(),
            $employeeRoles,
        );
        $permissions = $this->permissionRepository->findByRoleIds($roleIds);

        $matchingScopes = [];
        foreach ($permissions as $permission) {
            $matchesResource = $permission->resource() === $resource;
            $matchesAction = $permission->action() === $action
                || $permission->action() === PermissionAction::Manage;

            if ($matchesResource && $matchesAction) {
                $matchingScopes[] = $permission->scope();
            }
        }

        if ([] === $matchingScopes) {
            return null;
        }

        usort($matchingScopes, static fn (PermissionScope $a, PermissionScope $b) => self::SCOPE_PRIORITY[$b->value] <=> self::SCOPE_PRIORITY[$a->value]);

        return $matchingScopes[0];
    }

    /**
     * Resolve department-level permission scope with hierarchy inheritance.
     * Child department's explicit permission overrides parent's.
     */
    private function resolveDepartmentScope(
        DepartmentId $departmentId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?PermissionScope {
        // Build department chain: current dept + ancestors
        $departmentIds = [$departmentId->value()];
        $currentDept = $this->departmentRepository->findById($departmentId);

        // Walk up the parent chain to collect ancestor department IDs
        while (null !== $currentDept && null !== $currentDept->parentId()) {
            $departmentIds[] = $currentDept->parentId()->value();
            $currentDept = $this->departmentRepository->findById($currentDept->parentId());
        }

        // Batch load all department permissions
        $allDeptPermissions = $this->departmentPermissionRepository->findByDepartmentIds($departmentIds);

        // Find matching permissions for resource+action, ordered by department proximity
        // (child department comes first in $departmentIds, so its permission takes priority)
        foreach ($departmentIds as $deptId) {
            foreach ($allDeptPermissions as $dp) {
                if ($dp->departmentId()->value() === $deptId
                    && $dp->resource() === $resource
                    && ($dp->action() === $action || $dp->action() === PermissionAction::Manage)
                ) {
                    return $dp->scope();
                }
            }
        }

        return null;
    }

    /**
     * Return the wider of two scopes, or whichever is non-null.
     */
    private function getWiderScope(?PermissionScope $a, ?PermissionScope $b): ?PermissionScope
    {
        if (null === $a) {
            return $b;
        }
        if (null === $b) {
            return $a;
        }

        return self::SCOPE_PRIORITY[$a->value] >= self::SCOPE_PRIORITY[$b->value] ? $a : $b;
    }

    /**
     * @return list<\App\Organization\Domain\Entity\Permission>
     */
    private function getRolePermissions(EmployeeId $employeeId): array
    {
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId($employeeId);
        if ([] === $employeeRoles) {
            return [];
        }

        $roleIds = array_map(
            static fn ($er) => $er->roleId()->value(),
            $employeeRoles,
        );

        return $this->permissionRepository->findByRoleIds($roleIds);
    }

    /**
     * Get department permissions for employee's department and all ancestor departments.
     *
     * @return list<\App\Organization\Domain\Entity\DepartmentPermission>
     */
    private function getDepartmentPermissionsForEmployee(DepartmentId $departmentId): array
    {
        $departmentIds = [$departmentId->value()];
        $currentDept = $this->departmentRepository->findById($departmentId);

        while (null !== $currentDept && null !== $currentDept->parentId()) {
            $departmentIds[] = $currentDept->parentId()->value();
            $currentDept = $this->departmentRepository->findById($currentDept->parentId());
        }

        return $this->departmentPermissionRepository->findByDepartmentIds($departmentIds);
    }

    /**
     * @return list<string>
     */
    private function getDirectSubordinateIds(EmployeeId $managerId): array
    {
        $ids = [$managerId->value()];
        $subordinates = $this->employeeRepository->findByManagerId($managerId);

        foreach ($subordinates as $sub) {
            $ids[] = $sub->id()->value();
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function getSubordinateTreeIds(EmployeeId $managerId): array
    {
        $ids = [$managerId->value()];
        $queue = [$managerId];

        while ([] !== $queue) {
            $currentId = array_shift($queue);
            $subordinates = $this->employeeRepository->findByManagerId($currentId);

            foreach ($subordinates as $sub) {
                $ids[] = $sub->id()->value();
                $queue[] = $sub->id();
            }
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function getDepartmentEmployeeIds(mixed $employee): array
    {
        $employees = $this->employeeRepository->findByDepartmentId($employee->departmentId());

        return array_map(
            static fn ($emp) => $emp->id()->value(),
            $employees,
        );
    }

    /**
     * @return list<string>
     */
    private function getDepartmentTreeEmployeeIds(mixed $employee): array
    {
        $departmentIds = [$employee->departmentId()->value()];

        $descendants = $this->departmentRepository->findDescendants($employee->departmentId());
        foreach ($descendants as $dept) {
            $departmentIds[] = $dept->id()->value();
        }

        $allEmployees = [];
        foreach ($departmentIds as $deptId) {
            $deptEmployees = $this->employeeRepository->findByDepartmentId(
                DepartmentId::fromString($deptId),
            );

            foreach ($deptEmployees as $emp) {
                $allEmployees[$emp->id()->value()] = true;
            }
        }

        return array_keys($allEmployees);
    }

    /**
     * @return list<string>
     */
    private function getOrganizationEmployeeIds(OrganizationId $organizationId): array
    {
        $employees = $this->employeeRepository->findActiveByOrganizationId($organizationId);

        return array_map(
            static fn ($emp) => $emp->id()->value(),
            $employees,
        );
    }
}
