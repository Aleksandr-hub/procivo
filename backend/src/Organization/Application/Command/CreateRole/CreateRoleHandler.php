<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateRole;

use App\Organization\Domain\Entity\Role;
use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Exception\RoleAlreadyExistsException;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\RoleId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateRoleHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(CreateRoleCommand $command): void
    {
        $organizationId = OrganizationId::fromString($command->organizationId);

        if (null === $this->organizationRepository->findById($organizationId)) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        if (null !== $this->roleRepository->findByNameAndOrganizationId($command->name, $organizationId)) {
            throw RoleAlreadyExistsException::withName($command->name, $command->organizationId);
        }

        $role = Role::create(
            id: RoleId::fromString($command->id),
            organizationId: $organizationId,
            name: $command->name,
            description: $command->description,
            isSystem: $command->isSystem,
            hierarchy: $command->hierarchy,
        );

        $this->roleRepository->save($role);
    }
}
