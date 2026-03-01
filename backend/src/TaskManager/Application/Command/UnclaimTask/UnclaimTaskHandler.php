<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UnclaimTask;

use App\TaskManager\Domain\Exception\TaskClaimException;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UnclaimTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(UnclaimTaskCommand $command): void
    {
        $this->entityManager->wrapInTransaction(function () use ($command): void {
            $task = $this->taskRepository->findByIdForUpdate(
                TaskId::fromString($command->taskId),
            );

            if (null === $task) {
                throw TaskNotFoundException::withId($command->taskId);
            }

            if (!$task->isPoolTask()) {
                throw TaskClaimException::notAPoolTask($command->taskId);
            }

            if ($task->assigneeId() !== $command->employeeId) {
                throw TaskClaimException::notClaimed($command->taskId);
            }

            $task->unclaim($command->actorId);
            // No explicit save — wrapInTransaction calls flush() before commit
        });
    }
}
