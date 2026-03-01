<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class ProcessInstanceMigratedEvent implements DomainEvent
{
    public function __construct(
        public string $processInstanceId,
        public string $fromVersionId,
        public string $toVersionId,
        public string $migratedBy,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.process_instance.migrated';
    }
}
