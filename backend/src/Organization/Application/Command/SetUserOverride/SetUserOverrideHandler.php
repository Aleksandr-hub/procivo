<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SetUserOverride;

use App\Organization\Domain\Entity\UserPermissionOverride;
use App\Organization\Domain\Event\UserPermissionOverrideChangedEvent;
use App\Organization\Domain\Repository\UserPermissionOverrideRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PermissionAction;
use App\Organization\Domain\ValueObject\PermissionEffect;
use App\Organization\Domain\ValueObject\PermissionResource;
use App\Organization\Domain\ValueObject\PermissionScope;
use App\Organization\Domain\ValueObject\UserPermissionOverrideId;
use App\Shared\Application\Bus\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SetUserOverrideHandler
{
    public function __construct(
        private UserPermissionOverrideRepositoryInterface $userOverrideRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(SetUserOverrideCommand $command): void
    {
        $employeeId = EmployeeId::fromString($command->employeeId);
        $resource = PermissionResource::from($command->resource);
        $action = PermissionAction::from($command->action);

        $existing = $this->userOverrideRepository->findByEmployeeIdResourceAction(
            $employeeId,
            $resource,
            $action,
        );

        if (null !== $existing) {
            $this->userOverrideRepository->remove($existing);
        }

        $override = UserPermissionOverride::create(
            id: UserPermissionOverrideId::generate(),
            employeeId: $employeeId,
            organizationId: OrganizationId::fromString($command->organizationId),
            resource: $resource,
            action: $action,
            effect: PermissionEffect::from($command->effect),
            scope: PermissionScope::from($command->scope),
        );

        $this->userOverrideRepository->save($override);

        $this->eventBus->dispatch(new UserPermissionOverrideChangedEvent(
            employeeId: $command->employeeId,
            organizationId: $command->organizationId,
            resource: $command->resource,
            action: $command->action,
            effect: $command->effect,
            scope: $command->scope,
            actorId: $command->actorId,
        ));
    }
}
