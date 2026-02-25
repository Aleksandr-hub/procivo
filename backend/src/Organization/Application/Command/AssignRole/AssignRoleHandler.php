<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AssignRole;

use App\Organization\Domain\Entity\EmployeeRole;
use App\Organization\Domain\Event\RoleAssignedToEmployeeEvent;
use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Exception\RoleNotFoundException;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\RoleId;
use App\Shared\Application\Bus\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AssignRoleHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private RoleRepositoryInterface $roleRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(AssignRoleCommand $command): void
    {
        $employeeId = EmployeeId::fromString($command->employeeId);
        $roleId = RoleId::fromString($command->roleId);

        $employee = $this->employeeRepository->findById($employeeId);
        if (null === $employee) {
            throw EmployeeNotFoundException::withId($command->employeeId);
        }

        $role = $this->roleRepository->findById($roleId);
        if (null === $role) {
            throw RoleNotFoundException::withId($command->roleId);
        }

        $existing = $this->employeeRoleRepository->findByEmployeeIdAndRoleId($employeeId, $roleId);
        if (null !== $existing) {
            return;
        }

        $employeeRole = EmployeeRole::assign(
            id: Uuid::generate()->value(),
            employeeId: $employeeId,
            roleId: $roleId,
            organizationId: OrganizationId::fromString($command->organizationId),
        );

        $this->employeeRoleRepository->save($employeeRole);

        $this->eventBus->dispatch(new RoleAssignedToEmployeeEvent(
            $command->employeeId,
            $command->roleId,
            $command->organizationId,
        ));
    }
}
