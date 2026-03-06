<?php

declare(strict_types=1);

namespace App\Audit\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Audit log entry recording a domain event')]
final readonly class AuditLogDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed>|null $changes
     */
    public function __construct(
        #[OA\Property(description: 'Audit log entry UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Domain event type', example: 'task.created')]
        public string $eventType,
        #[OA\Property(description: 'Actor user UUID', format: 'uuid', nullable: true)]
        public ?string $actorId,
        #[OA\Property(description: 'Entity type', example: 'task')]
        public string $entityType,
        #[OA\Property(description: 'Entity UUID', format: 'uuid')]
        public string $entityId,
        #[OA\Property(description: 'Organization UUID', format: 'uuid', nullable: true)]
        public ?string $organizationId,
        #[OA\Property(description: 'Changed fields with old/new values', type: 'object', nullable: true)]
        public ?array $changes,
        #[OA\Property(description: 'Event timestamp', format: 'date-time')]
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        /** @var array<string, mixed>|null $changes */
        $changes = isset($row['changes']) && \is_string($row['changes'])
            ? json_decode($row['changes'], true, 512, \JSON_THROW_ON_ERROR)
            : (isset($row['changes']) && \is_array($row['changes']) ? $row['changes'] : null);

        return new self(
            id: (string) $row['id'],
            eventType: (string) $row['event_type'],
            actorId: isset($row['actor_id']) ? (string) $row['actor_id'] : null,
            entityType: (string) $row['entity_type'],
            entityId: (string) $row['entity_id'],
            organizationId: isset($row['organization_id']) ? (string) $row['organization_id'] : null,
            changes: $changes,
            occurredAt: new \DateTimeImmutable((string) $row['occurred_at']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->eventType,
            'actor_id' => $this->actorId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'organization_id' => $this->organizationId,
            'changes' => $this->changes,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
