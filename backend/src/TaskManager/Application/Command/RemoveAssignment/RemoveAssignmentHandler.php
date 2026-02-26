<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\RemoveAssignment;

use App\TaskManager\Domain\Repository\TaskAssignmentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RemoveAssignmentHandler
{
    public function __construct(
        private TaskAssignmentRepositoryInterface $assignmentRepository,
    ) {
    }

    public function __invoke(RemoveAssignmentCommand $command): void
    {
        $assignment = $this->assignmentRepository->findById($command->assignmentId);

        if (null === $assignment) {
            return; // Idempotent — already removed
        }

        $this->assignmentRepository->remove($assignment);
    }
}
