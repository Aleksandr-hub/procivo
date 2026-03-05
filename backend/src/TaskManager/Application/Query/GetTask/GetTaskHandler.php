<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTask;

use App\TaskManager\Application\DTO\TaskDTO;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Application\Port\UserQueryPort;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowInterface $taskStateMachine,
        private UserQueryPort $userQueryPort,
        private OrganizationQueryPort $organizationQueryPort,
        private EntityManagerInterface $entityManager,
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

        $creatorNames = $this->userQueryPort->resolveDisplayNamesWithAvatars([$task->creatorId()]);
        $creatorData = $creatorNames[$task->creatorId()] ?? null;
        $creatorName = $creatorData['name'] ?? ('system' === $task->creatorId() ? 'System' : null);
        $creatorAvatarUrl = $creatorData['avatarUrl'] ?? null;

        $assigneeName = null;
        $assigneeAvatarUrl = null;
        if (null !== $task->assigneeId()) {
            $assigneeNames = $this->organizationQueryPort->resolveEmployeeDisplayNames([$task->assigneeId()]);
            $assigneeData = $assigneeNames[$task->assigneeId()] ?? null;
            $assigneeName = $assigneeData['name'] ?? null;
            $assigneeAvatarUrl = $assigneeData['avatarUrl'] ?? null;
        }

        // Load comment count for this task via DBAL
        $conn = $this->entityManager->getConnection();
        $commentCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM task_manager_comments WHERE task_id = ?',
            [$task->id()->value()],
        );

        return TaskDTO::fromEntity(
            $task,
            $transitions,
            creatorName: $creatorName,
            creatorAvatarUrl: $creatorAvatarUrl,
            assigneeName: $assigneeName,
            assigneeAvatarUrl: $assigneeAvatarUrl,
            commentCount: $commentCount,
        );
    }
}
