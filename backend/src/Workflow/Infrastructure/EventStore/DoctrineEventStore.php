<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineEventStore implements EventStoreInterface
{
    public function __construct(
        private Connection $connection,
        private EventSerializer $serializer,
    ) {
    }

    public function append(string $aggregateId, DomainEvent $event, int $expectedVersion): void
    {
        $serialized = $this->serializer->serialize($event);

        $this->connection->insert('workflow_process_events', [
            'id' => Uuid::v7()->toRfc4122(),
            'aggregate_id' => $aggregateId,
            'event_type' => $serialized['event_type'],
            'payload' => json_encode($serialized['payload'], \JSON_THROW_ON_ERROR),
            'version' => $expectedVersion,
            'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * @return list<StoredEvent>
     */
    public function load(string $aggregateId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, aggregate_id, event_type, payload, version, occurred_at FROM workflow_process_events WHERE aggregate_id = :id ORDER BY version ASC',
            ['id' => $aggregateId],
        );

        return array_map(fn (array $row): StoredEvent => new StoredEvent(
            id: $row['id'],
            aggregateId: $row['aggregate_id'],
            eventType: $row['event_type'],
            payload: json_decode($row['payload'], true, 512, \JSON_THROW_ON_ERROR),
            version: (int) $row['version'],
            occurredAt: new \DateTimeImmutable($row['occurred_at']),
        ), $rows);
    }
}
