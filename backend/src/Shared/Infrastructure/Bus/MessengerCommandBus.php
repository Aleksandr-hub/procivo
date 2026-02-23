<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function dispatch(CommandInterface $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            /** @var \Throwable $nested */
            $nested = $e->getWrappedExceptions()[0] ?? $e;

            throw $nested;
        }
    }
}
