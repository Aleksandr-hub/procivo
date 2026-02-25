<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateRole;

use App\Organization\Domain\Exception\CannotDeleteSystemRoleException;
use App\Organization\Domain\Exception\RoleAlreadyExistsException;
use App\Organization\Domain\Exception\RoleNotFoundException;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\RoleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(UpdateRoleCommand $command): void
    {
        $role = $this->roleRepository->findById(RoleId::fromString($command->roleId));

        if (null === $role) {
            throw RoleNotFoundException::withId($command->roleId);
        }

        if ($role->isSystem() && $role->name() !== $command->name) {
            throw CannotDeleteSystemRoleException::withId($command->roleId);
        }

        $existing = $this->roleRepository->findByNameAndOrganizationId($command->name, $role->organizationId());
        if (null !== $existing && $existing->id()->value() !== $command->roleId) {
            throw RoleAlreadyExistsException::withName($command->name, $role->organizationId()->value());
        }

        $role->update($command->name, $command->description, $command->hierarchy);

        $this->roleRepository->save($role);
    }
}
