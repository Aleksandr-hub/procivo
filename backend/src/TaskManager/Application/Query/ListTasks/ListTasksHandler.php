<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListTasks;

use App\TaskManager\Application\DTO\TaskDTO;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListTasksHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private LabelRepositoryInterface $labelRepository,
        private WorkflowInterface $taskStateMachine,
        private OrganizationQueryPort $organizationQueryPort,
    ) {
    }

    /**
     * @return list<TaskDTO>
     */
    public function __invoke(ListTasksQuery $query): array
    {
        $status = null !== $query->status ? TaskStatus::from($query->status) : null;

        if (null !== $query->candidateEmployeeId) {
            $roleIds = $this->organizationQueryPort->getEmployeeRoleIds($query->candidateEmployeeId);
            $departmentId = $this->organizationQueryPort->getEmployeeDepartmentId($query->candidateEmployeeId);

            $tasks = $this->taskRepository->findAvailableForEmployee(
                $query->organizationId,
                $roleIds,
                $departmentId,
            );
        } else {
            $tasks = $this->taskRepository->findByOrganizationId(
                $query->organizationId,
                $status,
                $query->assigneeId,
            );
        }

        // Batch-load labels for all tasks
        $taskIds = array_map(static fn (Task $t) => $t->id()->value(), $tasks);
        $labelIdsByTask = $this->labelRepository->findLabelIdsByTaskIds(...$taskIds);

        // Build label entity lookup (unique label IDs)
        $allLabelIds = array_unique(array_merge(...array_values($labelIdsByTask)));
        $labelEntities = [];
        foreach ($allLabelIds as $labelId) {
            $label = $this->labelRepository->findById(new \App\TaskManager\Domain\ValueObject\LabelId($labelId));
            if (null !== $label) {
                $labelEntities[$labelId] = ['name' => $label->name(), 'color' => $label->color()];
            }
        }

        return array_map(
            function (Task $task) use ($labelIdsByTask, $labelEntities) {
                $taskLabels = [];
                foreach ($labelIdsByTask[$task->id()->value()] ?? [] as $labelId) {
                    if (isset($labelEntities[$labelId])) {
                        $taskLabels[] = $labelEntities[$labelId];
                    }
                }

                return TaskDTO::fromEntity(
                    $task,
                    $this->getAvailableTransitions($task),
                    $taskLabels,
                );
            },
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
