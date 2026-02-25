<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\EventBusInterface;
use App\Shared\Domain\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerEventBus implements EventBusInterface
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
