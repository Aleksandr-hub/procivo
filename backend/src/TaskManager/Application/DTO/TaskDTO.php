<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Task;

final readonly class TaskDTO
{
    /**
     * @param array<string, mixed>|null $formSchema
     * @param list<string> $availableTransitions
     */
    public function __construct(
        public string $id,
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
        public ?array $formSchema = null,
        public array $availableTransitions = [],
    ) {
    }

    /**
     * @param list<string> $availableTransitions
     */
    public static function fromEntity(Task $task, array $availableTransitions = []): self
    {
        return new self(
            id: $task->id()->value(),
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
            formSchema: $task->formSchema(),
            availableTransitions: $availableTransitions,
        );
    }
}
