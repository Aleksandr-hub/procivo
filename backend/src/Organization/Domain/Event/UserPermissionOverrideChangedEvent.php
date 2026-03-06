<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class UserPermissionOverrideChangedEvent implements DomainEvent
{
    public function __construct(
        public string $employeeId,
        public string $organizationId,
        public string $resource,
        public string $action,
        public string $effect,
        public string $scope,
        public string $actorId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.user_permission_override.changed';
    }
}
