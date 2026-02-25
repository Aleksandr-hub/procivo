<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Application\Query\QueryInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus, // @phpstan-ignore property.onlyWritten (used by HandleTrait)
    ) {
    }

    public function ask(QueryInterface $query): mixed
    {
        try {
            return $this->handle($query);
        } catch (HandlerFailedException $e) {
            /** @var \Throwable $nested */
            $nested = current($e->getWrappedExceptions()) ?: $e;

            throw $nested;
        }
    }
}
