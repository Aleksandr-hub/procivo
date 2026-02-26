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
            ->setParameter('taskId', $taskId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
