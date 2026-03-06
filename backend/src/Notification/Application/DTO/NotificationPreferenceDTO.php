<?php

declare(strict_types=1);

namespace App\Notification\Application\DTO;

use App\Notification\Domain\Entity\NotificationPreference;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'User notification preference per event type and channel')]
final readonly class NotificationPreferenceDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'User UUID', format: 'uuid')]
        public string $userId,
        #[OA\Property(description: 'Event type', example: 'task_assigned')]
        public string $eventType,
        #[OA\Property(description: 'Notification channel', enum: ['in_app', 'email'])]
        public string $channel,
        #[OA\Property(description: 'Whether this channel is enabled for the event type')]
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
