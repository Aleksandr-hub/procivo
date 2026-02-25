<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RevokeRole;

use App\Organization\Domain\Event\RoleRevokedFromEmployeeEvent;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\RoleId;
use App\Shared\Application\Bus\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RevokeRoleHandler
{
    public function __construct(
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(RevokeRoleCommand $command): void
    {
        $employeeId = EmployeeId::fromString($command->employeeId);
        $roleId = RoleId::fromString($command->roleId);

        $employeeRole = $this->employeeRoleRepository->findByEmployeeIdAndRoleId($employeeId, $roleId);

        if (null === $employeeRole) {
            return;
        }

        $organizationId = $employeeRole->organizationId()->value();

        $this->employeeRoleRepository->delete($employeeRole);

        $this->eventBus->dispatch(new RoleRevokedFromEmployeeEvent(
            $command->employeeId,
            $command->roleId,
            $organizationId,
        ));
    }
}
