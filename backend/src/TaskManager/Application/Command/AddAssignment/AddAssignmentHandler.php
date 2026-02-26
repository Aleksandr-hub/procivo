<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AddAssignment;

use App\TaskManager\Domain\Entity\TaskAssignment;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskAssignmentRepositoryInterface;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\AssignmentRole;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AddAssignmentHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskAssignmentRepositoryInterface $assignmentRepository,
    ) {
    }

    public function __invoke(AddAssignmentCommand $command): void
    {
        $taskId = TaskId::fromString($command->taskId);
        $task = $this->taskRepository->findById($taskId);

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        $role = AssignmentRole::from($command->role);

        // Check if already assigned with same role
        $existing = $this->assignmentRepository->findByTaskAndEmployee($taskId, $command->employeeId, $role->value);
        if (null !== $existing) {
            return; // Already assigned, idempotent
        }

        $assignment = TaskAssignment::create(
            id: $command->id,
            taskId: $taskId,
            employeeId: $command->employeeId,
            role: $role,
            assignedBy: $command->assignedBy,
        );

        $this->assignmentRepository->save($assignment);
    }
}
