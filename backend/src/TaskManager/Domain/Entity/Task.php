<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\Shared\Domain\AggregateRoot;
use App\TaskManager\Domain\Event\TaskAssignedEvent;
use App\TaskManager\Domain\Event\TaskClaimedEvent;
use App\TaskManager\Domain\Event\TaskCreatedEvent;
use App\TaskManager\Domain\Event\TaskDeletedEvent;
use App\TaskManager\Domain\Event\TaskStatusChangedEvent;
use App\TaskManager\Domain\Event\TaskUnclaimedEvent;
use App\TaskManager\Domain\Exception\TaskClaimException;
use App\TaskManager\Domain\ValueObject\AssignmentStrategy;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskPriority;
use App\TaskManager\Domain\ValueObject\TaskStatus;

class Task extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $title;
    private ?string $description;
    private string $status;
    private string $priority;
    private ?\DateTimeImmutable $dueDate;
    private ?float $estimatedHours;
    private ?string $assigneeId;
    private string $assignmentStrategy;
    private ?string $candidateRoleId;
    private ?string $candidateDepartmentId;
    /** @var array<string, mixed>|null */
    private ?array $formSchema = null;
    private int $sequenceNumber;
    private string $creatorId;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        TaskId $id,
        string $organizationId,
        string $title,
        ?string $description,
        TaskPriority $priority,
        ?\DateTimeImmutable $dueDate,
        ?float $estimatedHours,
        string $creatorId,
        int $sequenceNumber,
        ?string $assigneeId = null,
        AssignmentStrategy $assignmentStrategy = AssignmentStrategy::Unassigned,
        ?string $candidateRoleId = null,
        ?string $candidateDepartmentId = null,
        ?array $formSchema = null,
    ): self {
        $task = new self();
        $task->id = $id->value();
        $task->organizationId = $organizationId;
        $task->title = $title;
        $task->description = $description;
        $task->status = TaskStatus::Draft->value;
        $task->priority = $priority->value;
        $task->dueDate = $dueDate;
        $task->estimatedHours = $estimatedHours;
        $task->assigneeId = $assigneeId;
        $task->assignmentStrategy = $assignmentStrategy->value;
        $task->candidateRoleId = $candidateRoleId;
        $task->candidateDepartmentId = $candidateDepartmentId;
        $task->formSchema = $formSchema;
        $task->sequenceNumber = $sequenceNumber;
        $task->creatorId = $creatorId;
        $task->createdAt = new \DateTimeImmutable();
        $task->updatedAt = null;

        $task->recordEvent(new TaskCreatedEvent($id->value(), $organizationId, $title, $creatorId));

        return $task;
    }

    public function update(
        string $title,
        ?string $description,
        TaskPriority $priority,
        ?\DateTimeImmutable $dueDate,
        ?float $estimatedHours,
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->priority = $priority->value;
        $this->dueDate = $dueDate;
        $this->estimatedHours = $estimatedHours;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function assign(?string $assigneeId): void
    {
        $this->assigneeId = $assigneeId;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskAssignedEvent($this->id, $assigneeId));
    }

    public function claim(string $employeeId): void
    {
        if (!$this->isPoolTask()) {
            throw TaskClaimException::notAPoolTask($this->id);
        }

        if (null !== $this->assigneeId) {
            throw TaskClaimException::alreadyClaimed($this->id);
        }

        $this->assigneeId = $employeeId;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskClaimedEvent($this->id, $employeeId));
    }

    public function unclaim(): void
    {
        if (null === $this->assigneeId) {
            throw TaskClaimException::notClaimed($this->id);
        }

        $previousAssigneeId = $this->assigneeId;
        $this->assigneeId = null;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskUnclaimedEvent($this->id, $previousAssigneeId));
    }

    public function isPoolTask(): bool
    {
        return null !== $this->candidateRoleId || null !== $this->candidateDepartmentId;
    }

    public function markDeleted(): void
    {
        $this->recordEvent(new TaskDeletedEvent($this->id, $this->organizationId));
    }

    /**
     * Called by Symfony Workflow marking store (getStatus/setStatus).
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Called by Symfony Workflow marking store (getStatus/setStatus).
     */
    public function setStatus(string $status): void
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskStatusChangedEvent($this->id, $oldStatus, $status));
    }

    public function id(): TaskId
    {
        return TaskId::fromString($this->id);
    }

    public function sequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function status(): TaskStatus
    {
        return TaskStatus::from($this->status);
    }

    public function priority(): TaskPriority
    {
        return TaskPriority::from($this->priority);
    }

    public function dueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function estimatedHours(): ?float
    {
        return $this->estimatedHours;
    }

    public function assigneeId(): ?string
    {
        return $this->assigneeId;
    }

    public function assignmentStrategy(): AssignmentStrategy
    {
        return AssignmentStrategy::from($this->assignmentStrategy);
    }

    public function candidateRoleId(): ?string
    {
        return $this->candidateRoleId;
    }

    public function candidateDepartmentId(): ?string
    {
        return $this->candidateDepartmentId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function formSchema(): ?array
    {
        return $this->formSchema;
    }

    public function creatorId(): string
    {
        return $this->creatorId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
