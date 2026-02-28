<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\ClaimTask;

use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Domain\Exception\TaskClaimException;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ClaimTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private OrganizationQueryPort $organizationQueryPort,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ClaimTaskCommand $command): void
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

            if (null !== $task->assigneeId()) {
                throw TaskClaimException::alreadyClaimed($command->taskId);
            }

            $this->validateEligibility($command->employeeId, $task);

            $task->claim($command->employeeId);
            // No explicit save — wrapInTransaction calls flush() before commit
        });
    }

    private function validateEligibility(string $employeeId, object $task): void
    {
        $candidateRoleId = $task->candidateRoleId();
        $candidateDepartmentId = $task->candidateDepartmentId();

        $eligible = false;

        if (null !== $candidateRoleId) {
            $eligible = $this->organizationQueryPort->employeeBelongsToRole($employeeId, $candidateRoleId);
        }

        if (!$eligible && null !== $candidateDepartmentId) {
            $eligible = $this->organizationQueryPort->employeeBelongsToDepartment($employeeId, $candidateDepartmentId);
        }

        if (!$eligible) {
            throw TaskClaimException::notEligible($employeeId, $task->id()->value());
        }
    }
}
