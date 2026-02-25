<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetRole;

use App\Organization\Application\DTO\PermissionDTO;
use App\Organization\Application\DTO\RoleDTO;
use App\Organization\Domain\Exception\RoleNotFoundException;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\RoleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(GetRoleQuery $query): RoleDTO
    {
        $role = $this->roleRepository->findById(RoleId::fromString($query->roleId));

        if (null === $role) {
            throw RoleNotFoundException::withId($query->roleId);
        }

        $permissions = $this->permissionRepository->findByRoleId($role->id());
        $permissionDTOs = array_map(
            static fn ($p) => PermissionDTO::fromEntity($p),
            $permissions,
        );

        return RoleDTO::fromEntity($role, $permissionDTOs);
    }
}
