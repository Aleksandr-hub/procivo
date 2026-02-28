<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Workflow\Domain\Entity\WorkflowTaskLink;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineWorkflowTaskLinkRepository implements WorkflowTaskLinkRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(WorkflowTaskLink $link): void
    {
        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }

    public function findByTaskId(string $taskId): ?WorkflowTaskLink
    {
        return $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(WorkflowTaskLink::class, 'l')
            ->where('l.taskId = :taskId')
            ->andWhere('l.completedAt IS NULL')
            ->setParameter('taskId', $taskId)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<string> $taskIds
     *
     * @return list<WorkflowTaskLink>
     */
    public function findByTaskIds(array $taskIds): array
    {
        if ([] === $taskIds) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(WorkflowTaskLink::class, 'l')
            ->where('l.taskId IN (:taskIds)')
            ->setParameter('taskIds', $taskIds)
            ->getQuery()
            ->getResult();
    }

    public function findLatestByProcessInstanceId(string $processInstanceId): ?WorkflowTaskLink
    {
        return $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(WorkflowTaskLink::class, 'l')
            ->where('l.processInstanceId = :pid')
            ->setParameter('pid', $processInstanceId)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
