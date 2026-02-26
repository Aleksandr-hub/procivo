<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TokenMovedEvent implements DomainEvent
{
    public function __construct(
        public string $processInstanceId,
        public string $tokenId,
        public string $fromNodeId,
        public string $toNodeId,
        public string $transitionId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.token.moved';
    }
}
