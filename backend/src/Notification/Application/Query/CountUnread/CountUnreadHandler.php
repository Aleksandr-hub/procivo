<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\CountUnread;

use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class CountUnreadHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(CountUnreadQuery $query): int
    {
        return $this->notificationRepository->countUnreadByRecipientId($query->recipientId);
    }
}
