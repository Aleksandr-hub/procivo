<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListTasks;

use App\TaskManager\Application\DTO\TaskDTO;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Application\Port\UserQueryPort;
use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskStatus;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
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
        private UserQueryPort $userQueryPort,
        private EntityManagerInterface $entityManager,
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

        // Batch-resolve creator and assignee display names
        $creatorIds = array_unique(array_map(static fn (Task $t) => $t->creatorId(), $tasks));
        $assigneeIds = array_unique(array_filter(array_map(static fn (Task $t) => $t->assigneeId(), $tasks)));

        $creatorNameMap = $this->userQueryPort->resolveDisplayNamesWithAvatars(array_values($creatorIds));
        $assigneeNameMap = [] !== $assigneeIds
            ? $this->organizationQueryPort->resolveEmployeeDisplayNames(array_values($assigneeIds))
            : [];

        // Batch-load comment counts via DBAL (no N+1)
        $commentCounts = [];
        if ([] !== $taskIds) {
            $conn = $this->entityManager->getConnection();
            $commentCounts = $conn->fetchAllKeyValue(
                'SELECT task_id, COUNT(*) FROM task_manager_comments WHERE task_id IN (?) GROUP BY task_id',
                [$taskIds],
                [ArrayParameterType::STRING],
            );
        }

        return array_map(
            function (Task $task) use ($labelIdsByTask, $labelEntities, $creatorNameMap, $assigneeNameMap, $commentCounts) {
                $taskLabels = [];
                foreach ($labelIdsByTask[$task->id()->value()] ?? [] as $labelId) {
                    if (isset($labelEntities[$labelId])) {
                        $taskLabels[] = $labelEntities[$labelId];
                    }
                }

                $creatorData = $creatorNameMap[$task->creatorId()] ?? null;
                $creatorName = $creatorData['name'] ?? ('system' === $task->creatorId() ? 'System' : null);
                $creatorAvatarUrl = $creatorData['avatarUrl'] ?? null;

                $assigneeName = null;
                $assigneeAvatarUrl = null;
                if (null !== $task->assigneeId()) {
                    $assigneeData = $assigneeNameMap[$task->assigneeId()] ?? null;
                    $assigneeName = $assigneeData['name'] ?? null;
                    $assigneeAvatarUrl = $assigneeData['avatarUrl'] ?? null;
                }

                $commentCount = (int) ($commentCounts[$task->id()->value()] ?? 0);

                return TaskDTO::fromEntity(
                    $task,
                    $this->getAvailableTransitions($task),
                    $taskLabels,
                    $creatorName,
                    $creatorAvatarUrl,
                    $assigneeName,
                    $assigneeAvatarUrl,
                    $commentCount,
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
