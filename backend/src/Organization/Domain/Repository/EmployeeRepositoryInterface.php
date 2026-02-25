<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;

interface EmployeeRepositoryInterface
{
    public function save(Employee $employee): void;

    public function findById(EmployeeId $id): ?Employee;

    public function findByUserIdAndOrganizationId(string $userId, OrganizationId $organizationId): ?Employee;

    public function existsByUserIdAndOrganizationId(string $userId, OrganizationId $organizationId): bool;

    /**
     * @return list<Employee>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;

    /**
     * @return list<Employee>
     */
    public function findByDepartmentId(DepartmentId $departmentId): array;

    /**
     * @return list<Employee>
     */
    public function findByPositionId(PositionId $positionId): array;

    /**
     * @return list<Employee>
     */
    public function findByManagerId(EmployeeId $managerId): array;

    /**
     * @return list<Employee>
     */
    public function findActiveByOrganizationId(OrganizationId $organizationId): array;
}
