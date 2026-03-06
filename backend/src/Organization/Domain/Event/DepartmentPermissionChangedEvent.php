<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class DepartmentPermissionChangedEvent implements DomainEvent
{
    public function __construct(
        public string $departmentId,
        public string $organizationId,
        public string $resource,
        public string $action,
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
        return 'organization.department_permission.changed';
    }
}
