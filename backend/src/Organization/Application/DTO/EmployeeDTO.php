<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Employee;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Employee resource within an organization')]
final readonly class EmployeeDTO
{
    public function __construct(
        #[OA\Property(description: 'Employee UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Linked user UUID', format: 'uuid')]
        public string $userId,
        #[OA\Property(description: 'Position UUID', format: 'uuid')]
        public string $positionId,
        #[OA\Property(description: 'Department UUID', format: 'uuid')]
        public string $departmentId,
        #[OA\Property(description: 'Unique employee number')]
        public string $employeeNumber,
        #[OA\Property(description: 'Direct manager employee UUID', format: 'uuid', nullable: true)]
        public ?string $managerId,
        #[OA\Property(description: 'Hire date', format: 'date-time')]
        public string $hiredAt,
        #[OA\Property(description: 'Employee status', enum: ['active', 'inactive'])]
        public string $status,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Department name', nullable: true)]
        public ?string $departmentName = null,
        #[OA\Property(description: 'Position name', nullable: true)]
        public ?string $positionName = null,
        #[OA\Property(description: 'User full name', nullable: true)]
        public ?string $userFullName = null,
        #[OA\Property(description: 'User email address', format: 'email', nullable: true)]
        public ?string $userEmail = null,
        #[OA\Property(description: 'User avatar URL', format: 'uri', nullable: true)]
        public ?string $userAvatarUrl = null,
    ) {
    }

    public static function fromEntity(
        Employee $employee,
        ?string $departmentName = null,
        ?string $positionName = null,
        ?string $userFullName = null,
        ?string $userEmail = null,
        ?string $userAvatarUrl = null,
    ): self {
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
            departmentName: $departmentName,
            positionName: $positionName,
            userFullName: $userFullName,
            userEmail: $userEmail,
            userAvatarUrl: $userAvatarUrl,
        );
    }
}
