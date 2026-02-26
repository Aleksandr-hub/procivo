<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\TaskAssignment;

final readonly class TaskAssignmentDTO
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $employeeId,
        public string $role,
        public string $assignedBy,
        public string $assignedAt,
        public ?string $employeeName = null,
    ) {
    }

    public static function fromEntity(TaskAssignment $assignment, ?string $employeeName = null): self
    {
        return new self(
            id: $assignment->id(),
            taskId: $assignment->taskId(),
            employeeId: $assignment->employeeId(),
            role: $assignment->role()->value,
            assignedBy: $assignment->assignedBy(),
            assignedAt: $assignment->assignedAt()->format(\DateTimeInterface::ATOM),
            employeeName: $employeeName,
        );
    }
}
