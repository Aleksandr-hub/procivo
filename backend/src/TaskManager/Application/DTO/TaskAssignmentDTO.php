<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\TaskAssignment;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Task assignment linking an employee to a task')]
final readonly class TaskAssignmentDTO
{
    public function __construct(
        #[OA\Property(description: 'Assignment UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Task UUID', format: 'uuid')]
        public string $taskId,
        #[OA\Property(description: 'Assigned employee UUID', format: 'uuid')]
        public string $employeeId,
        #[OA\Property(description: 'Assignment role', enum: ['assignee', 'watcher'])]
        public string $role,
        #[OA\Property(description: 'Assigner user UUID', format: 'uuid')]
        public string $assignedBy,
        #[OA\Property(description: 'Assignment timestamp', format: 'date-time')]
        public string $assignedAt,
        #[OA\Property(description: 'Employee display name', nullable: true)]
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
