<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Role;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\RoleId;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findById(RoleId $id): ?Role;

    /**
     * @return list<Role>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;

    public function findByNameAndOrganizationId(string $name, OrganizationId $organizationId): ?Role;

    public function delete(Role $role): void;
}
