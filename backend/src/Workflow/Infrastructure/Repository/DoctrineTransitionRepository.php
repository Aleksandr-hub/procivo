<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Workflow\Domain\Entity\Transition;
use App\Workflow\Domain\Repository\TransitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\TransitionId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransitionRepository implements TransitionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Transition $transition): void
    {
        $this->entityManager->persist($transition);
        $this->entityManager->flush();
    }

    public function remove(Transition $transition): void
    {
        $this->entityManager->remove($transition);
        $this->entityManager->flush();
    }

    public function findById(TransitionId $id): ?Transition
    {
        return $this->entityManager->find(Transition::class, $id->value());
    }

    /**
     * @return list<Transition>
     */
    public function findByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transition::class, 't')
            ->where('t.processDefinitionId = :defId')
            ->setParameter('defId', $processDefinitionId->value())
            ->orderBy('t.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Transition>
     */
    public function findBySourceNodeId(NodeId $sourceNodeId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Transition::class, 't')
            ->where('t.sourceNodeId = :nodeId')
            ->setParameter('nodeId', $sourceNodeId->value())
            ->orderBy('t.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
