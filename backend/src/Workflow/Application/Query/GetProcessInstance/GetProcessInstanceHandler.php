<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessInstance;

use App\Workflow\Application\DTO\ProcessInstanceDTO;
use App\Workflow\Domain\Exception\ProcessInstanceNotFoundException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProcessInstanceHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function __invoke(GetProcessInstanceQuery $query): ProcessInstanceDTO
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM workflow_process_instances_view WHERE id = ?',
            [$query->processInstanceId],
        );

        if (false === $row) {
            throw ProcessInstanceNotFoundException::withId($query->processInstanceId);
        }

        /** @var array<array{token_id: string, fire_at: string}> $timers */
        $timers = $this->connection->fetchAllAssociative(
            'SELECT token_id, fire_at FROM workflow_scheduled_timers WHERE process_instance_id = ? AND fired_at IS NULL',
            [$query->processInstanceId],
        );

        $timerMap = [];
        foreach ($timers as $timer) {
            $timerMap[$timer['token_id']] = $timer['fire_at'];
        }

        return ProcessInstanceDTO::fromRow($row, $timerMap);
    }
}
