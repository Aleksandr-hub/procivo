<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TaskStatusChangedEvent implements DomainEvent
{
    public function __construct(
        public string $taskId,
        public string $oldStatus,
        public string $newStatus,
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
        return 'task_manager.task.status_changed';
    }
}
