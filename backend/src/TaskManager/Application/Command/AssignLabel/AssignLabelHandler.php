<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AssignLabel;

use App\TaskManager\Domain\Exception\LabelNotFoundException;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\LabelId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AssignLabelHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private LabelRepositoryInterface $labelRepository,
    ) {
    }

    public function __invoke(AssignLabelCommand $command): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));
        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        $label = $this->labelRepository->findById(LabelId::fromString($command->labelId));
        if (null === $label) {
            throw LabelNotFoundException::withId($command->labelId);
        }

        $this->labelRepository->assignToTask(
            LabelId::fromString($command->labelId),
            TaskId::fromString($command->taskId),
        );
    }
}
