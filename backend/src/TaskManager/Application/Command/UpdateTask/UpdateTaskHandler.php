<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateTask;

use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskPriority;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(UpdateTaskCommand $command): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        $task->update(
            title: $command->title,
            description: $command->description,
            priority: TaskPriority::from($command->priority),
            dueDate: null !== $command->dueDate ? new \DateTimeImmutable($command->dueDate) : null,
            estimatedHours: $command->estimatedHours,
        );

        $this->taskRepository->save($task);
    }
}
