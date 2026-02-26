<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class VariablesMergedEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $mergedData
     */
    public function __construct(
        public string $processInstanceId,
        public string $nodeId,
        public string $actionKey,
        public array $mergedData,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.process.variables.merged';
    }
}
