<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use App\Notification\Domain\ValueObject\NotificationType;
use App\TaskManager\Domain\Event\TaskStatusChangedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskStatusChanged
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(TaskStatusChangedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));

        if (null === $task) {
            return;
        }

        $recipientId = $task->assigneeId() ?? $task->creatorId();

        $notification = Notification::create(
            NotificationId::generate(),
            $recipientId,
            NotificationType::TaskStatusChanged,
            'Task status changed',
            \sprintf('Task "%s" status changed from %s to %s.', $task->title(), $event->oldStatus, $event->newStatus),
            $event->taskId,
        );

        $this->notificationRepository->save($notification);
    }
}
