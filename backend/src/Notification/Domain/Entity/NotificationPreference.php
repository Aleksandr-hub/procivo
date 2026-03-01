<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\Notification\Domain\ValueObject\NotificationPreferenceId;

class NotificationPreference
{
    private string $id;
    private string $userId;
    private string $eventType;
    private string $channel;
    private bool $enabled;

    private function __construct()
    {
    }

    public static function create(
        NotificationPreferenceId $id,
        string $userId,
        string $eventType,
        string $channel,
        bool $enabled,
    ): self {
        $preference = new self();
        $preference->id = $id->value();
        $preference->userId = $userId;
        $preference->eventType = $eventType;
        $preference->channel = $channel;
        $preference->enabled = $enabled;

        return $preference;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function updateEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
