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
     * @return array{items: list<ProcessInstanceDTO>, total: int, page: int, limit: int}
     */
    public function __invoke(ListProcessInstancesQuery $query): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->from('workflow_process_instances_view')
            ->where('organization_id = :orgId')
            ->setParameter('orgId', $query->organizationId);

        if (null !== $query->status) {
            $qb->andWhere('status = :status')
                ->setParameter('status', $query->status);
        }

        if (null !== $query->search && '' !== $query->search) {
            $qb->andWhere('definition_name ILIKE :search')
                ->setParameter('search', '%' . $query->search . '%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(*)')->executeQuery()->fetchOne();

        $offset = ($query->page - 1) * $query->limit;
        $qb->select('*')
            ->orderBy('started_at', 'DESC')
            ->setMaxResults($query->limit)
            ->setFirstResult($offset);

        /** @var list<array<string, mixed>> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return [
            'items' => array_map(
                static fn (array $row): ProcessInstanceDTO => ProcessInstanceDTO::fromRow($row),
                $rows,
            ),
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
