<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RemoveUserOverride;

use App\Organization\Domain\Event\UserPermissionOverrideChangedEvent;
use App\Organization\Domain\Repository\UserPermissionOverrideRepositoryInterface;
use App\Organization\Domain\ValueObject\UserPermissionOverrideId;
use App\Shared\Application\Bus\EventBusInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RemoveUserOverrideHandler
{
    public function __construct(
        private UserPermissionOverrideRepositoryInterface $userOverrideRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(RemoveUserOverrideCommand $command): void
    {
        $override = $this->userOverrideRepository->findById(
            UserPermissionOverrideId::fromString($command->overrideId),
        );

        if (null === $override) {
            throw new NotFoundHttpException(\sprintf('Override "%s" not found.', $command->overrideId));
        }

        $this->userOverrideRepository->remove($override);

        $this->eventBus->dispatch(new UserPermissionOverrideChangedEvent(
            employeeId: $override->employeeId()->value(),
            organizationId: $command->organizationId,
            resource: $override->resource()->value,
            action: $override->action()->value,
            effect: 'removed',
            scope: $override->scope()->value,
            actorId: $command->actorId,
        ));
    }
}
