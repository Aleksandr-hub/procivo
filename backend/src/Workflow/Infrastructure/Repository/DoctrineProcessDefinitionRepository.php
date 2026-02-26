<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Workflow\Domain\Entity\ProcessDefinition;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionStatus;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProcessDefinitionRepository implements ProcessDefinitionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ProcessDefinition $processDefinition): void
    {
        $this->entityManager->persist($processDefinition);
        $this->entityManager->flush();
    }

    public function remove(ProcessDefinition $processDefinition): void
    {
        $this->entityManager->remove($processDefinition);
        $this->entityManager->flush();
    }

    public function findById(ProcessDefinitionId $id): ?ProcessDefinition
    {
        return $this->entityManager->find(ProcessDefinition::class, $id->value());
    }

    /**
     * @return list<ProcessDefinition>
     */
    public function findByOrganizationId(string $organizationId, ?ProcessDefinitionStatus $status = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('d')
            ->from(ProcessDefinition::class, 'd')
            ->where('d.organizationId = :orgId')
            ->setParameter('orgId', $organizationId)
            ->orderBy('d.createdAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }
}
