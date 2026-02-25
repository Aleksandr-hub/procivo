<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\EmployeeRole;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\RoleId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineEmployeeRoleRepository implements EmployeeRoleRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(EmployeeRole $employeeRole): void
    {
        $this->entityManager->persist($employeeRole);
        $this->entityManager->flush();
    }

    /**
     * @return list<EmployeeRole>
     */
    public function findByEmployeeId(EmployeeId $employeeId): array
    {
        return $this->entityManager->getRepository(EmployeeRole::class)->findBy([
            'employeeId' => $employeeId->value(),
        ]);
    }

    /**
     * @return list<EmployeeRole>
     */
    public function findByRoleId(RoleId $roleId): array
    {
        return $this->entityManager->getRepository(EmployeeRole::class)->findBy([
            'roleId' => $roleId->value(),
        ]);
    }

    public function findByEmployeeIdAndRoleId(EmployeeId $employeeId, RoleId $roleId): ?EmployeeRole
    {
        return $this->entityManager->getRepository(EmployeeRole::class)->findOneBy([
            'employeeId' => $employeeId->value(),
            'roleId' => $roleId->value(),
        ]);
    }

    public function delete(EmployeeRole $employeeRole): void
    {
        $this->entityManager->remove($employeeRole);
        $this->entityManager->flush();
    }

    public function deleteByEmployeeId(EmployeeId $employeeId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(EmployeeRole::class, 'er')
            ->where('er.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId->value())
            ->getQuery()
            ->execute();
    }

    public function deleteByRoleId(RoleId $roleId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(EmployeeRole::class, 'er')
            ->where('er.roleId = :roleId')
            ->setParameter('roleId', $roleId->value())
            ->getQuery()
            ->execute();
    }
}
