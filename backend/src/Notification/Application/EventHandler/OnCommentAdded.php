<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\TaskManager\Domain\Event\CommentAddedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnCommentAdded
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private TaskRepositoryInterface $taskRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CommentAddedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));

        if (null === $task) {
            return;
        }

        $recipientId = $task->assigneeId() ?? $task->creatorId();

        // Do not notify the comment author
        if ($recipientId === $event->authorId) {
            return;
        }

        $user = $this->userRepository->findById(UserId::fromString($recipientId));
        $recipientEmail = null !== $user ? $user->email()->value() : null;

        $this->notificationDispatcher->dispatch([
            'recipientId' => $recipientId,
            'recipientEmail' => $recipientEmail,
            'type' => NotificationType::CommentAdded,
            'title' => 'New comment on task',
            'body' => \sprintf('A new comment was added to task "%s".', $task->title()),
            'relatedEntityId' => $event->taskId,
            'relatedEntityType' => 'task',
            'emailSubject' => \sprintf('New comment on task: %s', $task->title()),
            'emailTemplate' => 'email/notification/comment_added.html.twig',
            'emailContext' => ['taskTitle' => $task->title(), 'taskId' => $event->taskId],
        ]);
    }
}
