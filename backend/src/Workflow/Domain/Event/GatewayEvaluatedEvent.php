<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class GatewayEvaluatedEvent implements DomainEvent
{
    /**
     * @param list<string> $selectedTransitionIds
     */
    public function __construct(
        public string $processInstanceId,
        public string $nodeId,
        public string $tokenId,
        public array $selectedTransitionIds,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.gateway.evaluated';
    }
}
