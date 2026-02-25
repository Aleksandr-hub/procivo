<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Role;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\RoleId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    public function findById(RoleId $id): ?Role
    {
        return $this->entityManager->find(Role::class, $id->value());
    }

    /**
     * @return list<Role>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Role::class)->findBy(
            ['organizationId' => $organizationId->value()],
            ['hierarchy' => 'ASC'],
        );
    }

    public function findByNameAndOrganizationId(string $name, OrganizationId $organizationId): ?Role
    {
        return $this->entityManager->getRepository(Role::class)->findOneBy([
            'name' => $name,
            'organizationId' => $organizationId->value(),
        ]);
    }

    public function delete(Role $role): void
    {
        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }
}
