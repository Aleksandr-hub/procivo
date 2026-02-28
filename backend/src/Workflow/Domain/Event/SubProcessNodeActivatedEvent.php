<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class SubProcessNodeActivatedEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $subProcessConfig
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $processInstanceId,
        public string $organizationId,
        public string $nodeId,
        public string $tokenId,
        public array $subProcessConfig,
        public array $variables,
        public string $startedBy,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.sub_process_node.activated';
    }
}
