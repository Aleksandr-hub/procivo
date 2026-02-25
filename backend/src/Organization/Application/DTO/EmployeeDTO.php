<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Employee;

final readonly class EmployeeDTO
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $userId,
        public string $positionId,
        public string $departmentId,
        public string $employeeNumber,
        public ?string $managerId,
        public string $hiredAt,
        public string $status,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Employee $employee): self
    {
        return new self(
            id: $employee->id()->value(),
            organizationId: $employee->organizationId()->value(),
            userId: $employee->userId(),
            positionId: $employee->positionId()->value(),
            departmentId: $employee->departmentId()->value(),
            employeeNumber: $employee->employeeNumber()->value(),
            managerId: $employee->managerId()?->value(),
            hiredAt: $employee->hiredAt()->format(\DateTimeInterface::ATOM),
            status: $employee->status()->value,
            createdAt: $employee->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }
}
