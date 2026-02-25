<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Permission;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\ValueObject\PermissionId;
use App\Organization\Domain\ValueObject\RoleId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Permission $permission): void
    {
        $this->entityManager->persist($permission);
        $this->entityManager->flush();
    }

    public function findById(PermissionId $id): ?Permission
    {
        return $this->entityManager->find(Permission::class, $id->value());
    }

    /**
     * @return list<Permission>
     */
    public function findByRoleId(RoleId $roleId): array
    {
        return $this->entityManager->getRepository(Permission::class)->findBy([
            'roleId' => $roleId->value(),
        ]);
    }

    /**
     * @param list<string> $roleIds
     *
     * @return list<Permission>
     */
    public function findByRoleIds(array $roleIds): array
    {
        if ([] === $roleIds) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Permission::class, 'p')
            ->where('p.roleId IN (:roleIds)')
            ->setParameter('roleIds', $roleIds)
            ->getQuery()
            ->getResult();
    }

    public function delete(Permission $permission): void
    {
        $this->entityManager->remove($permission);
        $this->entityManager->flush();
    }

    public function deleteByRoleId(RoleId $roleId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(Permission::class, 'p')
            ->where('p.roleId = :roleId')
            ->setParameter('roleId', $roleId->value())
            ->getQuery()
            ->execute();
    }

    public function findByRoleIdResourceAndAction(RoleId $roleId, string $resource, string $action): ?Permission
    {
        return $this->entityManager->getRepository(Permission::class)->findOneBy([
            'roleId' => $roleId->value(),
            'resource' => $resource,
            'action' => $action,
        ]);
    }
}
