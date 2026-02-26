<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkAsRead;

use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class MarkAsReadHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(MarkAsReadCommand $command): void
    {
        $notification = $this->notificationRepository->findById(
            NotificationId::fromString($command->notificationId),
        );

        if (null === $notification || $notification->recipientId() !== $command->recipientId) {
            return;
        }

        $notification->markAsRead();
        $this->notificationRepository->save($notification);
    }
}
