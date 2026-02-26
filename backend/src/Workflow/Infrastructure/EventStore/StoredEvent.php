<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\EventStore;

final readonly class StoredEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $aggregateId,
        public string $eventType,
        public array $payload,
        public int $version,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
