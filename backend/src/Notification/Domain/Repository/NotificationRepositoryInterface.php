<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repository;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\ValueObject\NotificationId;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;

    public function findById(NotificationId $id): ?Notification;

    /** @return list<Notification> */
    public function findByRecipientId(string $recipientId, int $limit = 50, int $offset = 0): array;

    /** @return list<Notification> */
    public function findByRecipientIdAndType(string $recipientId, ?string $type, int $limit = 50, int $offset = 0): array;

    public function countUnreadByRecipientId(string $recipientId): int;

    public function markAllAsReadByRecipientId(string $recipientId): void;
}
