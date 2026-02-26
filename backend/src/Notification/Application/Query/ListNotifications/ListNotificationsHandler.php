<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotifications;

use App\Notification\Application\DTO\NotificationDTO;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListNotificationsHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    /** @return list<NotificationDTO> */
    public function __invoke(ListNotificationsQuery $query): array
    {
        $notifications = $this->notificationRepository->findByRecipientId(
            $query->recipientId,
            $query->limit,
            $query->offset,
        );

        return array_map(NotificationDTO::fromEntity(...), $notifications);
    }
}
