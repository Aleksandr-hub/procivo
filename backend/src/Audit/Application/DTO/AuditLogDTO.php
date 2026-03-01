<?php

declare(strict_types=1);

namespace App\Audit\Application\DTO;

final readonly class AuditLogDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed>|null $changes
     */
    public function __construct(
        public string $id,
        public string $eventType,
        public ?string $actorId,
        public string $entityType,
        public string $entityId,
        public ?string $organizationId,
        public ?array $changes,
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
