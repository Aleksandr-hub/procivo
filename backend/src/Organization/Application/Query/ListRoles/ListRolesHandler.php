<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListRoles;

use App\Organization\Application\DTO\PermissionDTO;
use App\Organization\Application\DTO\RoleDTO;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListRolesHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    /**
     * @return list<RoleDTO>
     */
    public function __invoke(ListRolesQuery $query): array
    {
        $roles = $this->roleRepository->findByOrganizationId(
            OrganizationId::fromString($query->organizationId),
        );

        return array_map(function ($role) {
            $permissions = $this->permissionRepository->findByRoleId($role->id());
            $permissionDTOs = array_map(
                static fn ($p) => PermissionDTO::fromEntity($p),
                $permissions,
            );

            return RoleDTO::fromEntity($role, $permissionDTOs);
        }, $roles);
    }
}
