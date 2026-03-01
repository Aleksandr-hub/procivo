<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Workflow\Domain\Event\ProcessCompletedEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessCompleted
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private Connection $connection,
    ) {
    }

    public function __invoke(ProcessCompletedEvent $event): void
    {
        // ProcessCompletedEvent is SYNC — use fast DBAL query to avoid Doctrine overhead
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

        // Sync handler: in_app + Mercure only — no email (too slow for sync context)
        $this->notificationDispatcher->dispatch([
            'recipientId' => $startedBy,
            'recipientEmail' => null,
            'type' => NotificationType::ProcessCompleted,
            'title' => 'Process completed',
            'body' => \sprintf('Process "%s" has been completed successfully.', $processName),
            'relatedEntityId' => $event->processInstanceId,
            'relatedEntityType' => 'process_instance',
            'emailTemplate' => null,
        ]);
    }
}
