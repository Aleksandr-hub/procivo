<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateTask;

use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskPriority;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(CreateTaskCommand $command): void
    {
        $task = Task::create(
            id: TaskId::fromString($command->id),
            organizationId: $command->organizationId,
            title: $command->title,
            description: $command->description,
            priority: TaskPriority::from($command->priority),
            dueDate: null !== $command->dueDate ? new \DateTimeImmutable($command->dueDate) : null,
            estimatedHours: $command->estimatedHours,
            creatorId: $command->creatorId,
            assigneeId: $command->assigneeId,
        );

        $this->taskRepository->save($task);
    }
}
