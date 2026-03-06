<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SetDepartmentPermissions;

use App\Organization\Domain\Entity\DepartmentPermission;
use App\Organization\Domain\Event\DepartmentPermissionChangedEvent;
use App\Organization\Domain\Repository\DepartmentPermissionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPermissionId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;
use App\Shared\Application\Bus\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SetDepartmentPermissionsHandler
{
    public function __construct(
        private DepartmentPermissionRepositoryInterface $departmentPermissionRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(SetDepartmentPermissionsCommand $command): void
    {
        $departmentId = DepartmentId::fromString($command->departmentId);

        $existing = $this->departmentPermissionRepository->findByDepartmentId($departmentId);
        foreach ($existing as $permission) {
            $this->departmentPermissionRepository->remove($permission);
        }

        foreach ($command->permissions as $entry) {
            $permission = DepartmentPermission::create(
                id: DepartmentPermissionId::generate(),
                departmentId: $departmentId,
                organizationId: OrganizationId::fromString($command->organizationId),
                resource: PermissionResource::from($entry['resource']),
                action: PermissionAction::from($entry['action']),
                scope: PermissionScope::from($entry['scope']),
            );

            $this->departmentPermissionRepository->save($permission);

            $this->eventBus->dispatch(new DepartmentPermissionChangedEvent(
                departmentId: $command->departmentId,
                organizationId: $command->organizationId,
                resource: $entry['resource'],
                action: $entry['action'],
                scope: $entry['scope'],
                actorId: $command->actorId,
            ));
        }
    }
}
