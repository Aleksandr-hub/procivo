<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class ProcessStartedEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $processInstanceId,
        public string $processDefinitionId,
        public string $versionId,
        public string $organizationId,
        public string $startedBy,
        public array $variables,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.process.started';
    }
}
