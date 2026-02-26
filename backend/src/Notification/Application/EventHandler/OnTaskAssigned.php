<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use App\Notification\Domain\ValueObject\NotificationType;
use App\TaskManager\Domain\Event\TaskAssignedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskAssigned
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(TaskAssignedEvent $event): void
    {
        if (null === $event->assigneeId) {
            return;
        }

        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));
        $taskTitle = null !== $task ? $task->title() : 'Unknown task';

        $notification = Notification::create(
            NotificationId::generate(),
            $event->assigneeId,
            NotificationType::TaskAssigned,
            'Task assigned to you',
            \sprintf('You have been assigned to task "%s".', $taskTitle),
            $event->taskId,
        );

        $this->notificationRepository->save($notification);
    }
}
