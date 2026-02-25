<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\EmployeeRole;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\RoleId;

interface EmployeeRoleRepositoryInterface
{
    public function save(EmployeeRole $employeeRole): void;

    /**
     * @return list<EmployeeRole>
     */
    public function findByEmployeeId(EmployeeId $employeeId): array;

    /**
     * @return list<EmployeeRole>
     */
    public function findByRoleId(RoleId $roleId): array;

    public function findByEmployeeIdAndRoleId(EmployeeId $employeeId, RoleId $roleId): ?EmployeeRole;

    public function delete(EmployeeRole $employeeRole): void;

    public function deleteByEmployeeId(EmployeeId $employeeId): void;

    public function deleteByRoleId(RoleId $roleId): void;
}
