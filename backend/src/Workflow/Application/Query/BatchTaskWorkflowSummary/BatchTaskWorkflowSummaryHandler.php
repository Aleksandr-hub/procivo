<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\BatchTaskWorkflowSummary;

use App\Workflow\Application\DTO\TaskWorkflowSummaryDTO;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class BatchTaskWorkflowSummaryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<string, TaskWorkflowSummaryDTO>
     */
    public function __invoke(BatchTaskWorkflowSummaryQuery $query): array
    {
        if ([] === $query->taskIds) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT DISTINCT ON (l.task_id) l.task_id, l.process_instance_id, l.completed_at, l.node_name, v.definition_name
            FROM workflow_task_links l
            INNER JOIN workflow_process_instances_view v ON l.process_instance_id = v.id
            WHERE l.task_id IN (?)
            ORDER BY l.task_id, l.created_at DESC
            SQL;

        /** @var list<array{task_id: string, process_instance_id: string, completed_at: string|null, node_name: string, definition_name: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            $sql,
            [$query->taskIds],
            [ArrayParameterType::STRING],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[$row['task_id']] = new TaskWorkflowSummaryDTO(
                processInstanceId: $row['process_instance_id'],
                processName: $row['definition_name'],
                nodeName: $row['node_name'],
                isCompleted: null !== $row['completed_at'],
            );
        }

        return $result;
    }
}
