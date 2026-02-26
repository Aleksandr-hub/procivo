<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AssignTask;

use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AssignTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(AssignTaskCommand $command): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        $task->assign($command->assigneeId);

        $this->taskRepository->save($task);
    }
}
