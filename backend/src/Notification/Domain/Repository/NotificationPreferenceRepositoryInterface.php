<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repository;

use App\Notification\Domain\Entity\NotificationPreference;

interface NotificationPreferenceRepositoryInterface
{
    public function save(NotificationPreference $preference): void;

    /** @return list<NotificationPreference> */
    public function findByUserId(string $userId): array;

    public function findByUserIdAndEventTypeAndChannel(string $userId, string $eventType, string $channel): ?NotificationPreference;

    /**
     * Returns true if notification is enabled for user+eventType+channel.
     * Default: in_app → true, email → false (opt-in).
     */
    public function isEnabled(string $userId, string $eventType, string $channel): bool;
}
