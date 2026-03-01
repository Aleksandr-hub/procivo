<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\TransitionTask;

use App\TaskManager\Domain\Exception\InvalidTaskTransitionException;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class TransitionTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowInterface $taskStateMachine,
    ) {
    }

    public function __invoke(TransitionTaskCommand $command): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        if (!$this->taskStateMachine->can($task, $command->transition)) {
            throw InvalidTaskTransitionException::forTransition($command->transition, $task->getStatus());
        }

        $task->withActorId($command->actorId);
        $this->taskStateMachine->apply($task, $command->transition);

        $this->taskRepository->save($task);
    }
}
