<?php

declare(strict_types=1);

namespace App\Organization\Application\EventHandler;

use App\Organization\Application\Command\SeedDefaultRoles\SeedDefaultRolesCommand;
use App\Organization\Domain\Event\OrganizationCreatedEvent;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class SeedDefaultRolesOnOrganizationCreated
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(OrganizationCreatedEvent $event): void
    {
        $this->commandBus->dispatch(new SeedDefaultRolesCommand($event->organizationId));
    }
}
