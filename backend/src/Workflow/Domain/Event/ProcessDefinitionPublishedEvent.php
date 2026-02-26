<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class ProcessDefinitionPublishedEvent implements DomainEvent
{
    public function __construct(
        public string $processDefinitionId,
        public string $versionId,
        public int $versionNumber,
        public string $publishedBy,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.process_definition.published';
    }
}
