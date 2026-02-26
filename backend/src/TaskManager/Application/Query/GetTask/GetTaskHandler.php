<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTask;

use App\TaskManager\Application\DTO\TaskDTO;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowInterface $taskStateMachine,
    ) {
    }

    public function __invoke(GetTaskQuery $query): TaskDTO
    {
        $task = $this->taskRepository->findById(TaskId::fromString($query->taskId));

        if (null === $task) {
            throw TaskNotFoundException::withId($query->taskId);
        }

        $metadataStore = $this->taskStateMachine->getMetadataStore();
        $transitions = array_values(array_map(
            static fn ($t) => $t->getName(),
            array_filter(
                $this->taskStateMachine->getEnabledTransitions($task),
                static fn ($t) => !($metadataStore->getTransitionMetadata($t)['internal'] ?? false),
            ),
        ));

        return TaskDTO::fromEntity($task, $transitions);
    }
}
