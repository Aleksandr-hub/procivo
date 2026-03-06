<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Task;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Task resource with full details')]
final readonly class TaskDTO
{
    /**
     * @param array<string, mixed>|null                $formSchema
     * @param list<string>                             $availableTransitions
     * @param list<array{name: string, color: string}> $labels
     */
    public function __construct(
        #[OA\Property(description: 'Task UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Auto-incrementing sequence number')]
        public int $sequenceNumber,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Task title')]
        public string $title,
        #[OA\Property(description: 'Task description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Task status', enum: ['open', 'in_progress', 'done', 'cancelled'])]
        public string $status,
        #[OA\Property(description: 'Task priority', enum: ['low', 'medium', 'high', 'urgent'])]
        public string $priority,
        #[OA\Property(description: 'Due date', format: 'date-time', nullable: true)]
        public ?string $dueDate,
        #[OA\Property(description: 'Estimated hours to complete', nullable: true)]
        public ?float $estimatedHours,
        #[OA\Property(description: 'Assignee user UUID', format: 'uuid', nullable: true)]
        public ?string $assigneeId,
        #[OA\Property(description: 'Assignment strategy', enum: ['direct', 'role_based', 'department_based', 'pool'])]
        public string $assignmentStrategy,
        #[OA\Property(description: 'Candidate role UUID for pool tasks', format: 'uuid', nullable: true)]
        public ?string $candidateRoleId,
        #[OA\Property(description: 'Candidate department UUID for pool tasks', format: 'uuid', nullable: true)]
        public ?string $candidateDepartmentId,
        #[OA\Property(description: 'Whether this task is available for pool claiming')]
        public bool $isPoolTask,
        #[OA\Property(description: 'Creator user UUID', format: 'uuid')]
        public string $creatorId,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Last update timestamp', format: 'date-time', nullable: true)]
        public ?string $updatedAt,
        #[OA\Property(description: 'Creator display name', nullable: true)]
        public ?string $creatorName = null,
        #[OA\Property(description: 'Creator avatar URL', format: 'uri', nullable: true)]
        public ?string $creatorAvatarUrl = null,
        #[OA\Property(description: 'Assignee display name', nullable: true)]
        public ?string $assigneeName = null,
        #[OA\Property(description: 'Assignee avatar URL', format: 'uri', nullable: true)]
        public ?string $assigneeAvatarUrl = null,
        #[OA\Property(description: 'Workflow form schema', type: 'object', nullable: true)]
        public ?array $formSchema = null,
        #[OA\Property(description: 'Available workflow transitions', type: 'array', items: new OA\Items(type: 'string'))]
        public array $availableTransitions = [],
        #[OA\Property(description: 'Attached labels', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'color', type: 'string'),
            ],
            type: 'object',
        ))]
        public array $labels = [],
        #[OA\Property(description: 'Number of comments on the task')]
        public int $commentCount = 0,
    ) {
    }

    /**
     * @param list<string>                             $availableTransitions
     * @param list<array{name: string, color: string}> $labels
     */
    public static function fromEntity(
        Task $task,
        array $availableTransitions = [],
        array $labels = [],
        ?string $creatorName = null,
        ?string $creatorAvatarUrl = null,
        ?string $assigneeName = null,
        ?string $assigneeAvatarUrl = null,
        int $commentCount = 0,
    ): self {
        return new self(
            id: $task->id()->value(),
            sequenceNumber: $task->sequenceNumber(),
            organizationId: $task->organizationId(),
            title: $task->title(),
            description: $task->description(),
            status: $task->status()->value,
            priority: $task->priority()->value,
            dueDate: $task->dueDate()?->format(\DateTimeInterface::ATOM),
            estimatedHours: $task->estimatedHours(),
            assigneeId: $task->assigneeId(),
            assignmentStrategy: $task->assignmentStrategy()->value,
            candidateRoleId: $task->candidateRoleId(),
            candidateDepartmentId: $task->candidateDepartmentId(),
            isPoolTask: $task->isPoolTask(),
            creatorId: $task->creatorId(),
            createdAt: $task->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $task->updatedAt()?->format(\DateTimeInterface::ATOM),
            creatorName: $creatorName,
            creatorAvatarUrl: $creatorAvatarUrl,
            assigneeName: $assigneeName,
            assigneeAvatarUrl: $assigneeAvatarUrl,
            formSchema: $task->formSchema(),
            availableTransitions: $availableTransitions,
            labels: $labels,
            commentCount: $commentCount,
        );
    }
}
