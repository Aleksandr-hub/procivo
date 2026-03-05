<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Task;

final readonly class TaskDTO
{
    /**
     * @param array<string, mixed>|null                $formSchema
     * @param list<string>                             $availableTransitions
     * @param list<array{name: string, color: string}> $labels
     */
    public function __construct(
        public string $id,
        public int $sequenceNumber,
        public string $organizationId,
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public ?string $dueDate,
        public ?float $estimatedHours,
        public ?string $assigneeId,
        public string $assignmentStrategy,
        public ?string $candidateRoleId,
        public ?string $candidateDepartmentId,
        public bool $isPoolTask,
        public string $creatorId,
        public string $createdAt,
        public ?string $updatedAt,
        public ?string $creatorName = null,
        public ?string $creatorAvatarUrl = null,
        public ?string $assigneeName = null,
        public ?string $assigneeAvatarUrl = null,
        public ?array $formSchema = null,
        public array $availableTransitions = [],
        public array $labels = [],
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
