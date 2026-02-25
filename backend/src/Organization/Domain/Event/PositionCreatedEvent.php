<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class PositionCreatedEvent implements DomainEvent
{
    public function __construct(
        public string $positionId,
        public string $organizationId,
        public string $departmentId,
        public string $name,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.position.created';
    }
}
