<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\GrantPermission;

use App\Organization\Domain\Entity\Permission;
use App\Organization\Domain\Exception\PermissionAlreadyExistsException;
use App\Organization\Domain\Exception\RoleNotFoundException;
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
final readonly class GrantPermissionHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(GrantPermissionCommand $command): void
    {
        $roleId = RoleId::fromString($command->roleId);

        if (null === $this->roleRepository->findById($roleId)) {
            throw RoleNotFoundException::withId($command->roleId);
        }

        $existing = $this->permissionRepository->findByRoleIdResourceAndAction(
            $roleId,
            $command->resource,
            $command->action,
        );

        if (null !== $existing) {
            throw PermissionAlreadyExistsException::forRoleResourceAndAction(
                $command->roleId,
                $command->resource,
                $command->action,
            );
        }

        $permission = Permission::create(
            id: PermissionId::fromString($command->id),
            roleId: $roleId,
            organizationId: OrganizationId::fromString($command->organizationId),
            resource: PermissionResource::from($command->resource),
            action: PermissionAction::from($command->action),
            scope: PermissionScope::from($command->scope),
        );

        $this->permissionRepository->save($permission);
    }
}
