<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineDepartmentRepository implements DepartmentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Department $department): void
    {
        $this->entityManager->persist($department);
        $this->entityManager->flush();
    }

    public function remove(Department $department): void
    {
        $this->entityManager->remove($department);
        $this->entityManager->flush();
    }

    public function findById(DepartmentId $id): ?Department
    {
        return $this->entityManager->find(Department::class, $id->value());
    }

    public function existsByCode(DepartmentCode $code, OrganizationId $organizationId): bool
    {
        return null !== $this->entityManager->getRepository(Department::class)->findOneBy([
            'code' => $code->value(),
            'organizationId' => $organizationId->value(),
        ]);
    }

    /**
     * @return list<Department>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Department::class)->findBy(
            ['organizationId' => $organizationId->value()],
            ['path' => 'ASC', 'sortOrder' => 'ASC'],
        );
    }

    /**
     * @return list<Department>
     */
    public function findByParentId(OrganizationId $organizationId, ?DepartmentId $parentId): array
    {
        return $this->entityManager->getRepository(Department::class)->findBy(
            [
                'organizationId' => $organizationId->value(),
                'parentId' => $parentId?->value(),
            ],
            ['sortOrder' => 'ASC'],
        );
    }

    /**
     * @return list<Department>
     */
    public function findDescendants(DepartmentId $departmentId): array
    {
        $department = $this->findById($departmentId);

        if (null === $department) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('d')
            ->from(Department::class, 'd')
            ->where('d.path LIKE :pathPrefix')
            ->andWhere('d.id != :selfId')
            ->setParameter('pathPrefix', $department->path()->value().'%')
            ->setParameter('selfId', $departmentId->value())
            ->orderBy('d.path', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
