<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\TaskManager\Domain\Event\TaskStatusChangedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskStatusChanged
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private TaskRepositoryInterface $taskRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(TaskStatusChangedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));

        if (null === $task) {
            return;
        }

        $recipientId = $task->assigneeId() ?? $task->creatorId();

        $notificationType = 'done' === $event->newStatus
            ? NotificationType::TaskCompleted
            : NotificationType::TaskStatusChanged;

        $user = $this->userRepository->findById(UserId::fromString($recipientId));
        $recipientEmail = null !== $user ? $user->email()->value() : null;

        $this->notificationDispatcher->dispatch([
            'recipientId' => $recipientId,
            'recipientEmail' => $recipientEmail,
            'type' => $notificationType,
            'title' => 'Task status changed',
            'body' => \sprintf('Task "%s" status changed from %s to %s.', $task->title(), $event->oldStatus, $event->newStatus),
            'relatedEntityId' => $event->taskId,
            'relatedEntityType' => 'task',
            'emailSubject' => \sprintf('Task status update: %s', $task->title()),
            'emailTemplate' => 'done' === $event->newStatus ? 'email/notification/task_completed.html.twig' : null,
            'emailContext' => ['taskTitle' => $task->title(), 'taskId' => $event->taskId, 'newStatus' => $event->newStatus],
        ]);
    }
}
