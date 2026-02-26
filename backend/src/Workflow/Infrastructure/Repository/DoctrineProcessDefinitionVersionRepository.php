<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProcessDefinitionVersionRepository implements ProcessDefinitionVersionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ProcessDefinitionVersion $version): void
    {
        $this->entityManager->persist($version);
        $this->entityManager->flush();
    }

    public function findById(ProcessDefinitionVersionId $id): ?ProcessDefinitionVersion
    {
        return $this->entityManager->find(ProcessDefinitionVersion::class, $id->value());
    }

    /**
     * @return list<ProcessDefinitionVersion>
     */
    public function findByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(ProcessDefinitionVersion::class, 'v')
            ->where('v.processDefinitionId = :defId')
            ->setParameter('defId', $processDefinitionId->value())
            ->orderBy('v.versionNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): ?ProcessDefinitionVersion
    {
        return $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(ProcessDefinitionVersion::class, 'v')
            ->where('v.processDefinitionId = :defId')
            ->setParameter('defId', $processDefinitionId->value())
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
