<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TaskNodeActivatedEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $taskConfig
     */
    public function __construct(
        public string $processInstanceId,
        public string $organizationId,
        public string $nodeId,
        public string $tokenId,
        public string $nodeName,
        public array $taskConfig,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.task_node.activated';
    }
}
