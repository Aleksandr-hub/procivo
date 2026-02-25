<?php

declare(strict_types=1);

namespace App\Organization\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class InvitationCreatedEvent implements DomainEvent
{
    public function __construct(
        public string $invitationId,
        public string $organizationId,
        public string $email,
        public string $invitedByUserId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'organization.invitation.created';
    }
}
