<?php

declare(strict_types=1);

namespace App\Notification\Application\DTO;

use App\Notification\Domain\Entity\NotificationPreference;

final readonly class NotificationPreferenceDTO implements \JsonSerializable
{
    public function __construct(
        public string $userId,
        public string $eventType,
        public string $channel,
        public bool $enabled,
    ) {
    }

    public static function fromEntity(NotificationPreference $preference): self
    {
        return new self(
            userId: $preference->userId(),
            eventType: $preference->eventType(),
            channel: $preference->channel(),
            enabled: $preference->enabled(),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'userId' => $this->userId,
            'eventType' => $this->eventType,
            'channel' => $this->channel,
            'enabled' => $this->enabled,
        ];
    }
}
