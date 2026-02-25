<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class EmployeeManagerChangedEvent implements DomainEvent
{
    public function __construct(
        public string $employeeId,
        public string $organizationId,
        public ?string $newManagerId,
        public ?string $previousManagerId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.employee.manager_changed';
    }
}
