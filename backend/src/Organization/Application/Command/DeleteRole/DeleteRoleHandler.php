<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeleteRole;

use App\Organization\Domain\Exception\CannotDeleteSystemRoleException;
use App\Organization\Domain\Exception\RoleNotFoundException;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\RoleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
    ) {
    }

    public function __invoke(DeleteRoleCommand $command): void
    {
        $roleId = RoleId::fromString($command->roleId);
        $role = $this->roleRepository->findById($roleId);

        if (null === $role) {
            throw RoleNotFoundException::withId($command->roleId);
        }

        if ($role->isSystem()) {
            throw CannotDeleteSystemRoleException::withId($command->roleId);
        }

        $this->employeeRoleRepository->deleteByRoleId($roleId);
        $this->permissionRepository->deleteByRoleId($roleId);
        $this->roleRepository->delete($role);
    }
}
