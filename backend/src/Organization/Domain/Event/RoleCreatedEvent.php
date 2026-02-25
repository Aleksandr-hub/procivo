<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class RoleCreatedEvent implements DomainEvent
{
    public function __construct(
        public string $roleId,
        public string $organizationId,
        public string $roleName,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.role.created';
    }
}
