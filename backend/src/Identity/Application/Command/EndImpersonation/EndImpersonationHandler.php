<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\EndImpersonation;

use App\Identity\Domain\Event\ImpersonationEndedEvent;
use App\Shared\Application\Bus\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class EndImpersonationHandler
{
    public function __construct(
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(EndImpersonationCommand $command): void
    {
        $this->eventBus->dispatch(new ImpersonationEndedEvent(
            adminUserId: $command->adminUserId,
        ));
    }
}
