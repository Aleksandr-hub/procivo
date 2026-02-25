<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class DepartmentMovedEvent implements DomainEvent
{
    public function __construct(
        public string $departmentId,
        public string $organizationId,
        public ?string $oldParentId,
        public ?string $newParentId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.department.moved';
    }
}
