<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;

interface EventStoreInterface
{
    public function append(string $aggregateId, DomainEvent $event, int $expectedVersion): void;

    /**
     * @return list<StoredEvent>
     */
    public function load(string $aggregateId): array;
}
