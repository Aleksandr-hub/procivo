<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TaskAssignedEvent implements DomainEvent
{
    public function __construct(
        public string $taskId,
        public ?string $assigneeId,
        public string $actorId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'task_manager.task.assigned';
    }
}
