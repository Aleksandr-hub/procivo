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

        return ProcessInstanceDTO::fromRow($row);
    }
}
