<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Position;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePositionRepository implements PositionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Position $position): void
    {
        $this->entityManager->persist($position);
        $this->entityManager->flush();
    }

    public function remove(Position $position): void
    {
        $this->entityManager->remove($position);
        $this->entityManager->flush();
    }

    public function findById(PositionId $id): ?Position
    {
        return $this->entityManager->find(Position::class, $id->value());
    }

    /**
     * @return list<Position>
     */
    public function findByDepartmentId(DepartmentId $departmentId): array
    {
        return $this->entityManager->getRepository(Position::class)->findBy(
            ['departmentId' => $departmentId->value()],
            ['sortOrder' => 'ASC'],
        );
    }

    /**
     * @return list<Position>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Position::class)->findBy(
            ['organizationId' => $organizationId->value()],
            ['sortOrder' => 'ASC'],
        );
    }
}
