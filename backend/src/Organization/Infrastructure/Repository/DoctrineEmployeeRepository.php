<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Employee $employee): void
    {
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
    }

    public function findById(EmployeeId $id): ?Employee
    {
        return $this->entityManager->find(Employee::class, $id->value());
    }

    public function findByUserIdAndOrganizationId(string $userId, OrganizationId $organizationId): ?Employee
    {
        return $this->entityManager->getRepository(Employee::class)->findOneBy([
            'userId' => $userId,
            'organizationId' => $organizationId->value(),
        ]);
    }

    public function existsByUserIdAndOrganizationId(string $userId, OrganizationId $organizationId): bool
    {
        return null !== $this->findByUserIdAndOrganizationId($userId, $organizationId);
    }

    /**
     * @return list<Employee>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Employee::class)->findBy([
            'organizationId' => $organizationId->value(),
        ]);
    }

    /**
     * @return list<Employee>
     */
    public function findByDepartmentId(DepartmentId $departmentId): array
    {
        return $this->entityManager->getRepository(Employee::class)->findBy([
            'departmentId' => $departmentId->value(),
        ]);
    }

    /**
     * @return list<Employee>
     */
    public function findByPositionId(PositionId $positionId): array
    {
        return $this->entityManager->getRepository(Employee::class)->findBy([
            'positionId' => $positionId->value(),
        ]);
    }

    /**
     * @return list<Employee>
     */
    public function findByManagerId(EmployeeId $managerId): array
    {
        return $this->entityManager->getRepository(Employee::class)->findBy([
            'managerId' => $managerId->value(),
        ]);
    }

    /**
     * @return list<Employee>
     */
    public function findActiveByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Employee::class)->findBy([
            'organizationId' => $organizationId->value(),
            'status' => 'active',
        ]);
    }
}
