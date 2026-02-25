<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class RoleRevokedFromEmployeeEvent implements DomainEvent
{
    public function __construct(
        public string $employeeId,
        public string $roleId,
        public string $organizationId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.role.revoked_from_employee';
    }
}
