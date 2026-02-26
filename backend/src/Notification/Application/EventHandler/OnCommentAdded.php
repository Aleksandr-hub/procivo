<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use App\Notification\Domain\ValueObject\NotificationType;
use App\TaskManager\Domain\Event\CommentAddedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnCommentAdded
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(CommentAddedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));

        if (null === $task) {
            return;
        }

        $recipientId = $task->assigneeId() ?? $task->creatorId();

        // Don't notify the comment author
        if ($recipientId === $event->authorId) {
            return;
        }

        $notification = Notification::create(
            NotificationId::generate(),
            $recipientId,
            NotificationType::CommentAdded,
            'New comment on task',
            \sprintf('A new comment was added to task "%s".', $task->title()),
            $event->taskId,
        );

        $this->notificationRepository->save($notification);
    }
}
