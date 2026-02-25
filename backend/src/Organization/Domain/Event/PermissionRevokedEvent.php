<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class PermissionRevokedEvent implements DomainEvent
{
    public function __construct(
        public string $permissionId,
        public string $roleId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.permission.revoked';
    }
}
