<?php

declare(strict_types=1);

namespace App\Audit\Application\Query\ListAuditLog;

use App\Audit\Application\DTO\AuditLogDTO;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAuditLogHandler
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array{items: list<AuditLogDTO>, total: int, page: int, limit: int}
     */
    public function __invoke(ListAuditLogQuery $query): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->from('audit_log')
            ->where('organization_id = :orgId')
            ->setParameter('orgId', $query->organizationId);

        if (null !== $query->entityType) {
            $qb->andWhere('entity_type = :entityType')
                ->setParameter('entityType', $query->entityType);
        }

        if (null !== $query->entityId) {
            $qb->andWhere('entity_id = :entityId')
                ->setParameter('entityId', $query->entityId);
        }

        if (null !== $query->actorId) {
            $qb->andWhere('actor_id = :actorId')
                ->setParameter('actorId', $query->actorId);
        }

        if (null !== $query->dateFrom) {
            $qb->andWhere('occurred_at >= :dateFrom')
                ->setParameter('dateFrom', $query->dateFrom->format('Y-m-d H:i:s'));
        }

        if (null !== $query->dateTo) {
            $qb->andWhere('occurred_at <= :dateTo')
                ->setParameter('dateTo', $query->dateTo->format('Y-m-d H:i:s'));
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(*)')->executeQuery()->fetchOne();

        $offset = ($query->page - 1) * $query->limit;
        $qb->select('*')
            ->orderBy('occurred_at', 'DESC')
            ->setMaxResults($query->limit)
            ->setFirstResult($offset);

        /** @var list<array<string, mixed>> $rows */
        $rows = $qb->executeQuery()->fetchAllAssociative();

        return [
            'items' => array_map(
                static fn (array $row): AuditLogDTO => AuditLogDTO::fromRow($row),
                $rows,
            ),
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
