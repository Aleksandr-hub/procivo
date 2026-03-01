<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Workflow\Domain\Event\ProcessCancelledEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessCancelled
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private Connection $connection,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ProcessCancelledEvent $event): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT started_by, definition_name FROM workflow_process_instances_view WHERE id = :id',
            ['id' => $event->processInstanceId],
        );

        if (false === $row) {
            return;
        }

        /** @var string $startedBy */
        $startedBy = $row['started_by'];

        /** @var string $processName */
        $processName = $row['definition_name'];

        // Notify the process starter
        $starterUser = $this->userRepository->findById(UserId::fromString($startedBy));
        $starterEmail = null !== $starterUser ? $starterUser->email()->value() : null;

        $this->notificationDispatcher->dispatch([
            'recipientId' => $startedBy,
            'recipientEmail' => $starterEmail,
            'type' => NotificationType::ProcessCancelled,
            'title' => 'Process cancelled',
            'body' => \sprintf('Process "%s" has been cancelled.', $processName),
            'relatedEntityId' => $event->processInstanceId,
            'relatedEntityType' => 'process_instance',
            'emailSubject' => \sprintf('Process cancelled: %s', $processName),
            'emailTemplate' => 'email/notification/process_cancelled.html.twig',
            'emailContext' => ['processName' => $processName, 'processInstanceId' => $event->processInstanceId],
        ]);

        // Also notify the person who cancelled (if different from starter)
        if ($event->cancelledBy !== $startedBy) {
            $cancellerUser = $this->userRepository->findById(UserId::fromString($event->cancelledBy));
            $cancellerEmail = null !== $cancellerUser ? $cancellerUser->email()->value() : null;

            $this->notificationDispatcher->dispatch([
                'recipientId' => $event->cancelledBy,
                'recipientEmail' => $cancellerEmail,
                'type' => NotificationType::ProcessCancelled,
                'title' => 'Process cancellation confirmed',
                'body' => \sprintf('Process "%s" has been successfully cancelled.', $processName),
                'relatedEntityId' => $event->processInstanceId,
                'relatedEntityType' => 'process_instance',
                'emailSubject' => \sprintf('Process cancelled: %s', $processName),
                'emailTemplate' => 'email/notification/process_cancelled.html.twig',
                'emailContext' => ['processName' => $processName, 'processInstanceId' => $event->processInstanceId],
            ]);
        }
    }
}
