<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class TwoFactorDisabledEvent implements DomainEvent
{
    public function __construct(
        public string $userId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'identity.user.two_factor_disabled';
    }
}
