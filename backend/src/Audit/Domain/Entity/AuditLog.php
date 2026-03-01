<?php

declare(strict_types=1);

namespace App\Audit\Domain\Entity;

use App\Audit\Domain\ValueObject\AuditLogId;

/**
 * Append-only audit log entry. Never updated, only created.
 */
class AuditLog
{
    private string $id;
    private string $eventType;
    private ?string $actorId;
    private string $entityType;
    private string $entityId;
    private ?string $organizationId;
    /** @var array<string, mixed>|null */
    private ?array $changes;
    private \DateTimeImmutable $occurredAt;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed>|null $changes
     */
    public static function record(
        AuditLogId $id,
        string $eventType,
        ?string $actorId,
        string $entityType,
        string $entityId,
        ?string $organizationId,
        ?array $changes,
        \DateTimeImmutable $occurredAt,
    ): self {
        $log = new self();
        $log->id = $id->value();
        $log->eventType = $eventType;
        $log->actorId = $actorId;
        $log->entityType = $entityType;
        $log->entityId = $entityId;
        $log->organizationId = $organizationId;
        $log->changes = $changes;
        $log->occurredAt = $occurredAt;

        return $log;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function actorId(): ?string
    {
        return $this->actorId;
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function organizationId(): ?string
    {
        return $this->organizationId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function changes(): ?array
    {
        return $this->changes;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
