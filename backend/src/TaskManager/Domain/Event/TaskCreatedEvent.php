<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TaskCreatedEvent implements DomainEvent
{
    public function __construct(
        public string $taskId,
        public string $organizationId,
        public string $title,
        public string $creatorId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'task_manager.task.created';
    }
}
