<?php

declare(strict_types=1);

namespace App\Organization\Application\EventHandler;

use App\Organization\Application\Command\AssignRole\AssignRoleCommand;
use App\Organization\Domain\Event\EmployeeHiredEvent;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class AssignDefaultRoleOnEmployeeHired
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(EmployeeHiredEvent $event): void
    {
        $employeeRole = $this->roleRepository->findByNameAndOrganizationId(
            'Employee',
            OrganizationId::fromString($event->organizationId),
        );

        if (null === $employeeRole) {
            return;
        }

        $this->commandBus->dispatch(new AssignRoleCommand(
            employeeId: $event->employeeId,
            roleId: $employeeRole->id()->value(),
            organizationId: $event->organizationId,
        ));
    }
}
