<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationSlug;

interface OrganizationRepositoryInterface
{
    public function save(Organization $organization): void;

    public function findById(OrganizationId $id): ?Organization;

    public function findBySlug(OrganizationSlug $slug): ?Organization;

    public function existsBySlug(OrganizationSlug $slug): bool;

    /**
     * @return list<Organization>
     */
    public function findByOwnerUserId(string $userId): array;

    /**
     * @return list<Organization>
     */
    public function findByMemberUserId(string $userId): array;
}
