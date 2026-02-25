<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Security\Service;

use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
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

        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId($employee->id());
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
                \App\Organization\Domain\ValueObject\DepartmentId::fromString($deptId),
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
