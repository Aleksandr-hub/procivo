<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListTasks;

use App\TaskManager\Application\DTO\TaskDTO;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListTasksHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowInterface $taskStateMachine,
    ) {
    }

    /**
     * @return list<TaskDTO>
     */
    public function __invoke(ListTasksQuery $query): array
    {
        $status = null !== $query->status ? TaskStatus::from($query->status) : null;

        $tasks = $this->taskRepository->findByOrganizationId(
            $query->organizationId,
            $status,
            $query->assigneeId,
        );

        return array_map(
            fn ($task) => TaskDTO::fromEntity(
                $task,
                $this->getAvailableTransitions($task),
            ),
            $tasks,
        );
    }

    /**
     * @return list<string>
     */
    private function getAvailableTransitions(object $task): array
    {
        $metadataStore = $this->taskStateMachine->getMetadataStore();

        return array_values(array_map(
            static fn ($t) => $t->getName(),
            array_filter(
                $this->taskStateMachine->getEnabledTransitions($task),
                static fn ($t) => !($metadataStore->getTransitionMetadata($t)['internal'] ?? false),
            ),
        ));
    }
}
