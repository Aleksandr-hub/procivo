<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class CommentAddedEvent implements DomainEvent
{
    public function __construct(
        public string $commentId,
        public string $taskId,
        public string $authorId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'task_manager.comment.added';
    }
}
