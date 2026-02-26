<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class BoardCreatedEvent implements DomainEvent
{
    public function __construct(
        public string $boardId,
        public string $organizationId,
        public string $name,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'task_manager.board.created';
    }
}
