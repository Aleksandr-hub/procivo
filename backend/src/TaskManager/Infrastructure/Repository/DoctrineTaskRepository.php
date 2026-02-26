<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Task $task): void
    {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function remove(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function findById(TaskId $id): ?Task
    {
        return $this->entityManager->find(Task::class, $id->value());
    }

    /**
     * @return list<Task>
     */
    public function findByOrganizationId(string $organizationId, ?TaskStatus $status = null, ?string $assigneeId = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't')
            ->where('t.organizationId = :orgId')
            ->setParameter('orgId', $organizationId)
            ->orderBy('t.createdAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status->value);
        }

        if (null !== $assigneeId) {
            $qb->andWhere('t.assigneeId = :assigneeId')
                ->setParameter('assigneeId', $assigneeId);
        }

        return $qb->getQuery()->getResult();
    }
}
