<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SeedDefaultRoles;

use App\Organization\Domain\Entity\Permission;
use App\Organization\Domain\Entity\Role;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionId;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;
use App\Organization\Domain\ValueObject\RoleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SeedDefaultRolesHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(SeedDefaultRolesCommand $command): void
    {
        $organizationId = OrganizationId::fromString($command->organizationId);

        $existing = $this->roleRepository->findByOrganizationId($organizationId);
        if ([] !== $existing) {
            return;
        }

        $this->createAdminRole($organizationId);
        $this->createManagerRole($organizationId);
        $this->createEmployeeRole($organizationId);
    }

    private function createAdminRole(OrganizationId $organizationId): void
    {
        $roleId = RoleId::generate();
        $role = Role::create($roleId, $organizationId, 'Admin', 'Full access to all resources', true, 0);
        $this->roleRepository->save($role);

        foreach (PermissionResource::cases() as $resource) {
            $permission = Permission::create(
                PermissionId::generate(),
                $roleId,
                $organizationId,
                $resource,
                PermissionAction::Manage,
                PermissionScope::Organization,
            );
            $this->permissionRepository->save($permission);
        }
    }

    private function createManagerRole(OrganizationId $organizationId): void
    {
        $roleId = RoleId::generate();
        $role = Role::create($roleId, $organizationId, 'Manager', 'Manage subordinates and department', true, 50);
        $this->roleRepository->save($role);

        $permissions = [
            [PermissionResource::Employee, PermissionAction::View, PermissionScope::SubordinatesTree],
            [PermissionResource::Employee, PermissionAction::Update, PermissionScope::Subordinates],
            [PermissionResource::Department, PermissionAction::View, PermissionScope::DepartmentTree],
            [PermissionResource::Position, PermissionAction::View, PermissionScope::Organization],
            [PermissionResource::Invitation, PermissionAction::Create, PermissionScope::Department],
            [PermissionResource::Role, PermissionAction::View, PermissionScope::Organization],
        ];

        foreach ($permissions as [$resource, $action, $scope]) {
            $permission = Permission::create(
                PermissionId::generate(),
                $roleId,
                $organizationId,
                $resource,
                $action,
                $scope,
            );
            $this->permissionRepository->save($permission);
        }
    }

    private function createEmployeeRole(OrganizationId $organizationId): void
    {
        $roleId = RoleId::generate();
        $role = Role::create($roleId, $organizationId, 'Employee', 'Basic employee access', true, 100);
        $this->roleRepository->save($role);

        $permissions = [
            [PermissionResource::Employee, PermissionAction::View, PermissionScope::Own],
            [PermissionResource::Department, PermissionAction::View, PermissionScope::Organization],
            [PermissionResource::Position, PermissionAction::View, PermissionScope::Organization],
            [PermissionResource::Role, PermissionAction::View, PermissionScope::Own],
        ];

        foreach ($permissions as [$resource, $action, $scope]) {
            $permission = Permission::create(
                PermissionId::generate(),
                $roleId,
                $organizationId,
                $resource,
                $action,
                $scope,
            );
            $this->permissionRepository->save($permission);
        }
    }
}
