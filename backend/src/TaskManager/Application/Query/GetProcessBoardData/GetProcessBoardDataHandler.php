<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetProcessBoardData;

use App\TaskManager\Application\DTO\ProcessBoardDataDTO;
use App\TaskManager\Application\DTO\ProcessBoardInstanceDTO;
use App\TaskManager\Application\DTO\ProcessBoardMetricsDTO;
use App\TaskManager\Domain\Exception\BoardNotFoundException;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProcessBoardDataHandler
{
    public function __construct(
        private Connection $connection,
        private BoardRepositoryInterface $boardRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
    ) {
    }

    public function __invoke(GetProcessBoardDataQuery $query): ProcessBoardDataDTO
    {
        $board = $this->boardRepository->findById(BoardId::fromString($query->boardId));

        if (null === $board) {
            throw BoardNotFoundException::withId($query->boardId);
        }

        if ('process_board' !== $board->boardType()) {
            throw new \InvalidArgumentException(
                sprintf('Board "%s" is not a process board.', $query->boardId)
            );
        }

        $processDefinitionId = $board->processDefinitionId();
        if (null === $processDefinitionId) {
            throw new \LogicException(
                sprintf('Process board "%s" has no associated process definition.', $query->boardId)
            );
        }

        // Load nodesSnapshot for active node name resolution (cached per request)
        $nodeNameMap = $this->buildNodeNameMap($processDefinitionId);

        // Fetch process instances
        $rows = $this->connection->fetchAllAssociative(
            'SELECT pi.id, pi.status, pi.started_at, pi.definition_name, pi.tokens
             FROM workflow_process_instances_view pi
             WHERE pi.organization_id = :orgId AND pi.definition_id = :defId
             ORDER BY pi.started_at DESC',
            [
                'orgId' => $query->organizationId,
                'defId' => $processDefinitionId,
            ],
        );

        // Parse tokens and collect instance IDs
        $instanceIds = [];
        /** @var array<string, array{activeNodeId: ?string}> $instanceTokenData */
        $instanceTokenData = [];

        foreach ($rows as $row) {
            $instanceId = (string) $row['id'];
            $instanceIds[] = $instanceId;

            $tokens = json_decode((string) $row['tokens'], true, 512, \JSON_THROW_ON_ERROR);
            $activeNodeId = null;

            if (\is_array($tokens)) {
                foreach ($tokens as $token) {
                    if (\is_array($token) && ($token['status'] ?? '') === 'waiting') {
                        $activeNodeId = isset($token['node_id']) ? (string) $token['node_id'] : null;
                        break;
                    }
                }
            }

            $instanceTokenData[$instanceId] = ['activeNodeId' => $activeNodeId];
        }

        // Fetch active tasks for all instances in a single query
        /** @var array<string, array{taskId: string, assigneeId: ?string}> $activeTaskByInstance */
        $activeTaskByInstance = [];

        if ([] !== $instanceIds) {
            $taskLinks = $this->connection->fetchAllAssociative(
                'SELECT wtl.process_instance_id, t.id as task_id, t.assignee_id
                 FROM workflow_task_links wtl
                 JOIN task_manager_tasks t ON t.id = wtl.task_id
                 WHERE wtl.process_instance_id IN (?)
                   AND t.status NOT IN (\'done\', \'cancelled\')
                   AND wtl.completed_at IS NULL',
                [$instanceIds],
                [ArrayParameterType::STRING],
            );

            foreach ($taskLinks as $link) {
                $instanceId = (string) $link['process_instance_id'];
                if (!isset($activeTaskByInstance[$instanceId])) {
                    $activeTaskByInstance[$instanceId] = [
                        'taskId' => (string) $link['task_id'],
                        'assigneeId' => isset($link['assignee_id']) ? (string) $link['assignee_id'] : null,
                    ];
                }
            }
        }

        // Batch-resolve assignee display names
        $assigneeIds = array_unique(array_filter(
            array_map(
                static fn (array $t): ?string => $t['assigneeId'],
                $activeTaskByInstance,
            ),
        ));

        $assigneeNameMap = $this->resolveAssigneeNames(array_values($assigneeIds));

        // Build instance DTOs
        $instances = [];
        foreach ($rows as $row) {
            $instanceId = (string) $row['id'];
            $activeNodeId = $instanceTokenData[$instanceId]['activeNodeId'] ?? null;
            $activeNodeName = null !== $activeNodeId ? ($nodeNameMap[$activeNodeId] ?? null) : null;

            $activeTaskData = $activeTaskByInstance[$instanceId] ?? null;
            $activeTaskId = $activeTaskData['taskId'] ?? null;
            $assigneeId = $activeTaskData['assigneeId'] ?? null;
            $activeTaskAssigneeName = null !== $assigneeId ? ($assigneeNameMap[$assigneeId] ?? null) : null;

            $instances[] = new ProcessBoardInstanceDTO(
                id: $instanceId,
                name: (string) $row['definition_name'],
                status: (string) $row['status'],
                startedAt: (string) $row['started_at'],
                activeNodeId: $activeNodeId,
                activeNodeName: $activeNodeName,
                activeTaskId: $activeTaskId,
                activeTaskAssigneeName: $activeTaskAssigneeName,
            );
        }

        $metrics = $this->buildMetrics($query->organizationId);

        return new ProcessBoardDataDTO(
            instances: $instances,
            metrics: $metrics,
        );
    }

    /**
     * Build a map of node_id => node_name from the latest published version snapshot.
     *
     * @return array<string, string>
     */
    private function buildNodeNameMap(string $processDefinitionId): array
    {
        $version = $this->versionRepository->findLatestByProcessDefinitionId(
            ProcessDefinitionId::fromString($processDefinitionId)
        );

        if (null === $version) {
            return [];
        }

        $snapshot = $version->nodesSnapshot();
        $nodes = $snapshot['nodes'] ?? [];

        /** @var array<string, string> $map */
        $map = [];
        foreach ($nodes as $node) {
            if (isset($node['id'], $node['name'])) {
                $map[(string) $node['id']] = (string) $node['name'];
            }
        }

        return $map;
    }

    /**
     * Resolve assignee display names using a direct DBAL query.
     *
     * @param list<string> $assigneeIds
     *
     * @return array<string, string>
     */
    private function resolveAssigneeNames(array $assigneeIds): array
    {
        if ([] === $assigneeIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT u.id, u.first_name, u.last_name
             FROM identity_users u
             WHERE u.id IN (?)',
            [$assigneeIds],
            [ArrayParameterType::STRING],
        );

        /** @var array<string, string> $map */
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['id']] = trim((string) $row['first_name'].' '.(string) $row['last_name']);
        }

        return $map;
    }

    /**
     * Build pipeline metrics: total active instances and completed-per-day sparkline for last 14 days.
     */
    private function buildMetrics(string $organizationId): ProcessBoardMetricsDTO
    {
        $totalActive = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM workflow_process_instances_view WHERE organization_id = :orgId AND status = 'running'",
            ['orgId' => $organizationId],
        );

        $since = (new \DateTimeImmutable())->modify('-14 days')->format('Y-m-d 00:00:00');

        $completedRows = $this->connection->fetchAllAssociative(
            "SELECT DATE(occurred_at) as d, COUNT(*) as cnt
             FROM audit_log
             WHERE event_type = 'process.completed'
               AND organization_id = :orgId
               AND occurred_at >= :since
             GROUP BY DATE(occurred_at)
             ORDER BY d",
            [
                'orgId' => $organizationId,
                'since' => $since,
            ],
        );

        /** @var list<array{date: string, count: int}> $completedByDay */
        $completedByDay = array_map(
            static fn (array $r): array => [
                'date' => (string) $r['d'],
                'count' => (int) $r['cnt'],
            ],
            $completedRows,
        );

        return new ProcessBoardMetricsDTO(
            totalActive: $totalActive,
            completedByDay: $completedByDay,
        );
    }
}
