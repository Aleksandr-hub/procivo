<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Notification\Application\Service\NotificationDispatcher;
use App\Notification\Domain\ValueObject\NotificationType;
use App\TaskManager\Domain\Event\TaskAssignedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskAssigned
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private TaskRepositoryInterface $taskRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(TaskAssignedEvent $event): void
    {
        if (null === $event->assigneeId) {
            return;
        }

        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));
        $taskTitle = null !== $task ? $task->title() : 'Unknown task';

        $user = $this->userRepository->findById(UserId::fromString($event->assigneeId));
        $recipientEmail = null !== $user ? $user->email()->value() : null;

        $this->notificationDispatcher->dispatch([
            'recipientId' => $event->assigneeId,
            'recipientEmail' => $recipientEmail,
            'type' => NotificationType::TaskAssigned,
            'title' => 'Task assigned to you',
            'body' => \sprintf('You have been assigned to task "%s".', $taskTitle),
            'relatedEntityId' => $event->taskId,
            'relatedEntityType' => 'task',
            'emailSubject' => \sprintf('Task assigned: %s', $taskTitle),
            'emailTemplate' => 'email/notification/task_assigned.html.twig',
            'emailContext' => ['taskTitle' => $taskTitle, 'taskId' => $event->taskId],
        ]);
    }
}
