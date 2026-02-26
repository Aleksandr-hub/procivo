<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\ListProcessInstances;

use App\Workflow\Application\DTO\ProcessInstanceDTO;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListProcessInstancesHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<ProcessInstanceDTO>
     */
    public function __invoke(ListProcessInstancesQuery $query): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('workflow_process_instances_view')
            ->where('organization_id = :orgId')
            ->setParameter('orgId', $query->organizationId)
            ->orderBy('started_at', 'DESC');

        if (null !== $query->status) {
            $qb->andWhere('status = :status')
                ->setParameter('status', $query->status);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(
            static fn (array $row): ProcessInstanceDTO => ProcessInstanceDTO::fromRow($row),
            $rows,
        );
    }
}
