<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Organization;

use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\RoleId;
use App\TaskManager\Application\Port\OrganizationQueryPort;

final readonly class DoctrineOrganizationQueryAdapter implements OrganizationQueryPort
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
    ) {
    }

    /**
     * @return list<array{employeeId: string}>
     */
    public function findActiveEmployeeIdsByRoleId(string $roleId, string $organizationId): array
    {
        $employeeRoles = $this->employeeRoleRepository->findByRoleId(RoleId::fromString($roleId));

        $result = [];
        foreach ($employeeRoles as $employeeRole) {
            $employee = $this->employeeRepository->findById($employeeRole->employeeId());
            if (null !== $employee && $employee->isActive()) {
                $result[] = ['employeeId' => $employee->id()->value()];
            }
        }

        return $result;
    }

    /**
     * @return list<array{employeeId: string}>
     */
    public function findActiveEmployeeIdsByDepartmentId(string $departmentId): array
    {
        $employees = $this->employeeRepository->findByDepartmentId(DepartmentId::fromString($departmentId));

        $result = [];
        foreach ($employees as $employee) {
            if ($employee->isActive()) {
                $result[] = ['employeeId' => $employee->id()->value()];
            }
        }

        return $result;
    }

    public function employeeBelongsToRole(string $employeeId, string $roleId): bool
    {
        return null !== $this->employeeRoleRepository->findByEmployeeIdAndRoleId(
            EmployeeId::fromString($employeeId),
            RoleId::fromString($roleId),
        );
    }

    public function employeeBelongsToDepartment(string $employeeId, string $departmentId): bool
    {
        $employee = $this->employeeRepository->findById(EmployeeId::fromString($employeeId));

        return null !== $employee && $employee->departmentId()->value() === $departmentId;
    }

    /**
     * @return list<string>
     */
    public function getEmployeeRoleIds(string $employeeId): array
    {
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId(EmployeeId::fromString($employeeId));

        return array_map(
            static fn ($er) => $er->roleId()->value(),
            $employeeRoles,
        );
    }

    public function getEmployeeDepartmentId(string $employeeId): ?string
    {
        $employee = $this->employeeRepository->findById(EmployeeId::fromString($employeeId));

        return $employee?->departmentId()->value();
    }
}
