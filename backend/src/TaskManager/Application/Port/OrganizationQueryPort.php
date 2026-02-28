<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Port;

interface OrganizationQueryPort
{
    /**
     * @return list<array{employeeId: string}>
     */
    public function findActiveEmployeeIdsByRoleId(string $roleId, string $organizationId): array;

    /**
     * @return list<array{employeeId: string}>
     */
    public function findActiveEmployeeIdsByDepartmentId(string $departmentId): array;

    public function employeeBelongsToRole(string $employeeId, string $roleId): bool;

    public function employeeBelongsToDepartment(string $employeeId, string $departmentId): bool;

    /**
     * @return list<string>
     */
    public function getEmployeeRoleIds(string $employeeId): array;

    public function getEmployeeDepartmentId(string $employeeId): ?string;
}
