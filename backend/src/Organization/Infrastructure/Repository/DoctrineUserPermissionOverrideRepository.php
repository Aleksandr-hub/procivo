<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\UserPermissionOverride;
use App\Organization\Domain\Repository\UserPermissionOverrideRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserPermissionOverrideRepository implements UserPermissionOverrideRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(UserPermissionOverride $override): void
    {
        $this->entityManager->persist($override);
        $this->entityManager->flush();
    }

    public function remove(UserPermissionOverride $override): void
    {
        $this->entityManager->remove($override);
        $this->entityManager->flush();
    }

    /**
     * @return list<UserPermissionOverride>
     */
    public function findByEmployeeId(EmployeeId $employeeId): array
    {
        return $this->entityManager->getRepository(UserPermissionOverride::class)->findBy([
            'employeeId' => $employeeId->value(),
        ]);
    }

    public function findByEmployeeIdResourceAction(
        EmployeeId $employeeId,
        PermissionResource $resource,
        PermissionAction $action,
    ): ?UserPermissionOverride {
        return $this->entityManager->getRepository(UserPermissionOverride::class)->findOneBy([
            'employeeId' => $employeeId->value(),
            'resource' => $resource->value,
            'action' => $action->value,
        ]);
    }
}
