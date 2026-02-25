<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\RoleId;

class EmployeeRole
{
    private string $id;
    private string $employeeId;
    private string $roleId;
    private string $organizationId;
    private \DateTimeImmutable $assignedAt;

    private function __construct()
    {
    }

    public static function assign(
        string $id,
        EmployeeId $employeeId,
        RoleId $roleId,
        OrganizationId $organizationId,
    ): self {
        $employeeRole = new self();
        $employeeRole->id = $id;
        $employeeRole->employeeId = $employeeId->value();
        $employeeRole->roleId = $roleId->value();
        $employeeRole->organizationId = $organizationId->value();
        $employeeRole->assignedAt = new \DateTimeImmutable();

        return $employeeRole;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function employeeId(): EmployeeId
    {
        return EmployeeId::fromString($this->employeeId);
    }

    public function roleId(): RoleId
    {
        return RoleId::fromString($this->roleId);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function assignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
