<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateTask;

use App\TaskManager\Application\Service\AssignmentResolver;
use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\AssignmentStrategy;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskPriority;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private AssignmentResolver $assignmentResolver,
    ) {
    }

    public function __invoke(CreateTaskCommand $command): void
    {
        $assigneeId = $command->assigneeId;
        $strategy = AssignmentStrategy::Unassigned;
        $candidateRoleId = null;
        $candidateDepartmentId = null;

        if ('unassigned' !== $command->assignmentStrategy) {
            $result = $this->assignmentResolver->resolve(
                $command->assignmentStrategy,
                $command->organizationId,
                $command->assigneeEmployeeId,
                $command->assigneeRoleId,
                $command->assigneeDepartmentId,
            );

            $strategy = $result->strategy;
            $candidateRoleId = $result->candidateRoleId;
            $candidateDepartmentId = $result->candidateDepartmentId;

            if (null !== $result->assigneeId) {
                $assigneeId = $result->assigneeId;
            }
        }

        $sequenceNumber = $this->taskRepository->nextSequenceNumber($command->organizationId);

        $task = Task::create(
            id: TaskId::fromString($command->id),
            organizationId: $command->organizationId,
            title: $command->title,
            description: $command->description,
            priority: TaskPriority::from($command->priority),
            dueDate: null !== $command->dueDate ? new \DateTimeImmutable($command->dueDate) : null,
            estimatedHours: $command->estimatedHours,
            creatorId: $command->creatorId,
            sequenceNumber: $sequenceNumber,
            assigneeId: $assigneeId,
            assignmentStrategy: $strategy,
            candidateRoleId: $candidateRoleId,
            candidateDepartmentId: $candidateDepartmentId,
            formSchema: $command->formSchema,
        );

        $this->taskRepository->save($task);
    }
}
