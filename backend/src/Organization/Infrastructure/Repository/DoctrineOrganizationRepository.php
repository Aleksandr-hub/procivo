<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Repository;

use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeStatus;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationSlug;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrganizationRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Organization $organization): void
    {
        $this->entityManager->persist($organization);
        $this->entityManager->flush();
    }

    public function findById(OrganizationId $id): ?Organization
    {
        return $this->entityManager->find(Organization::class, $id->value());
    }

    public function findBySlug(OrganizationSlug $slug): ?Organization
    {
        return $this->entityManager->getRepository(Organization::class)->findOneBy([
            'slug' => $slug->value(),
        ]);
    }

    public function existsBySlug(OrganizationSlug $slug): bool
    {
        return null !== $this->findBySlug($slug);
    }

    /**
     * @return list<Organization>
     */
    public function findByOwnerUserId(string $userId): array
    {
        return $this->entityManager->getRepository(Organization::class)->findBy([
            'ownerUserId' => $userId,
        ]);
    }

    /**
     * @return list<Organization>
     */
    public function findByMemberUserId(string $userId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->where(
                'o.ownerUserId = :userId OR o.id IN ('
                .'SELECT DISTINCT e.organizationId FROM '.Employee::class.' e '
                .'WHERE e.userId = :userId AND e.status != :dismissed'
                .')',
            )
            ->setParameter('userId', $userId)
            ->setParameter('dismissed', EmployeeStatus::Dismissed->value)
            ->getQuery()
            ->getResult();
    }
}
