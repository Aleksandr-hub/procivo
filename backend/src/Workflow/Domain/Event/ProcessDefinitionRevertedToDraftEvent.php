<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class ProcessDefinitionRevertedToDraftEvent implements DomainEvent
{
    public function __construct(
        public string $processDefinitionId,
        public string $revertedBy,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.process_definition.reverted_to_draft';
    }
}
