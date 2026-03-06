<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\DepartmentPermission;
use App\Organization\Domain\Repository\DepartmentPermissionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineDepartmentPermissionRepository implements DepartmentPermissionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(DepartmentPermission $permission): void
    {
        $this->entityManager->persist($permission);
        $this->entityManager->flush();
    }

    public function remove(DepartmentPermission $permission): void
    {
        $this->entityManager->remove($permission);
        $this->entityManager->flush();
    }

    /**
     * @return list<DepartmentPermission>
     */
    public function findByDepartmentId(DepartmentId $departmentId): array
    {
        return $this->entityManager->getRepository(DepartmentPermission::class)->findBy([
            'departmentId' => $departmentId->value(),
        ]);
    }

    /**
     * @param list<string> $departmentIds
     *
     * @return list<DepartmentPermission>
     */
    public function findByDepartmentIds(array $departmentIds): array
    {
        if ([] === $departmentIds) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('dp')
            ->from(DepartmentPermission::class, 'dp')
            ->where('dp.departmentId IN (:ids)')
            ->setParameter('ids', $departmentIds)
            ->getQuery()
            ->getResult();
    }

    public function findByDepartmentIdResourceAction(
        DepartmentId $departmentId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?DepartmentPermission {
        return $this->entityManager->getRepository(DepartmentPermission::class)->findOneBy([
            'departmentId' => $departmentId->value(),
            'resource' => $resource->value,
            'action' => $action->value,
        ]);
    }
}
