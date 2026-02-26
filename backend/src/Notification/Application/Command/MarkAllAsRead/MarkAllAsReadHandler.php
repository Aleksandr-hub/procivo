<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkAllAsRead;

use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class MarkAllAsReadHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(MarkAllAsReadCommand $command): void
    {
        $this->notificationRepository->markAllAsReadByRecipientId($command->recipientId);
    }
}
