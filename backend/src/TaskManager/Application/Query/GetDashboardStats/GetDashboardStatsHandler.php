<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetDashboardStats;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDashboardStatsHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array{
     *     tasks_by_status: array<string, int>,
     *     tasks_completed_by_day: list<array{day: string, cnt: int}>,
     *     processes_by_status: array<string, int>
     * }
     */
    public function __invoke(GetDashboardStatsQuery $query): array
    {
        /** @var array<string, int> $tasksByStatus */
        $tasksByStatus = $this->connection->executeQuery(
            'SELECT status, COUNT(*) as cnt FROM task_manager_tasks WHERE organization_id = :orgId GROUP BY status',
            ['orgId' => $query->organizationId],
        )->fetchAllKeyValue();

        $since = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
        $completedByDayRows = $this->connection->executeQuery(
            "SELECT DATE(updated_at) as day, COUNT(*) as cnt FROM task_manager_tasks WHERE organization_id = :orgId AND status = 'done' AND updated_at >= :since GROUP BY DATE(updated_at) ORDER BY day",
            ['orgId' => $query->organizationId, 'since' => $since],
        )->fetchAllAssociative();

        /** @var list<array{day: string, cnt: int}> $completedByDay */
        $completedByDay = array_map(
            static fn (array $row): array => ['day' => (string) $row['day'], 'cnt' => (int) $row['cnt']],
            $completedByDayRows,
        );

        /** @var array<string, int> $processesByStatus */
        $processesByStatus = $this->connection->executeQuery(
            'SELECT status, COUNT(*) as cnt FROM workflow_process_instances_view WHERE organization_id = :orgId GROUP BY status',
            ['orgId' => $query->organizationId],
        )->fetchAllKeyValue();

        return [
            'tasks_by_status' => $tasksByStatus,
            'tasks_completed_by_day' => $completedByDay,
            'processes_by_status' => $processesByStatus,
        ];
    }
}
