<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessInstanceHistory;

use App\Workflow\Application\DTO\ProcessEventDTO;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProcessInstanceHistoryHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<ProcessEventDTO>
     */
    public function __invoke(GetProcessInstanceHistoryQuery $query): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, event_type, payload, version, occurred_at FROM workflow_process_events WHERE aggregate_id = ? ORDER BY version ASC',
            [$query->processInstanceId],
        );

        return array_map(static function (array $row): ProcessEventDTO {
            /** @var array<string, mixed> $payload */
            $payload = \is_string($row['payload']) ? json_decode($row['payload'], true, 512, \JSON_THROW_ON_ERROR) : ($row['payload'] ?? []);

            return new ProcessEventDTO(
                id: (string) $row['id'],
                eventType: (string) $row['event_type'],
                payload: $payload,
                version: (int) $row['version'],
                occurredAt: (string) $row['occurred_at'],
            );
        }, $rows);
    }
}
