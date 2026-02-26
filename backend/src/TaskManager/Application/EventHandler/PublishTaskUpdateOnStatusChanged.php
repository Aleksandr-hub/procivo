<?php

declare(strict_types=1);

namespace App\TaskManager\Application\EventHandler;

use App\TaskManager\Domain\Event\TaskStatusChangedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Infrastructure\Mercure\TaskMercurePublisher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class PublishTaskUpdateOnStatusChanged
{
    public function __construct(
        private TaskMercurePublisher $publisher,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(TaskStatusChangedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));

        if (null === $task) {
            return;
        }

        $this->publisher->publishTaskUpdate(
            $task->organizationId(),
            'task.status_changed',
            [
                'taskId' => $event->taskId,
                'oldStatus' => $event->oldStatus,
                'newStatus' => $event->newStatus,
            ],
        );
    }
}
