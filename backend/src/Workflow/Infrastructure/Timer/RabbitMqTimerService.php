<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Timer;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final readonly class RabbitMqTimerService implements TimerServiceInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function scheduleTimer(string $processInstanceId, string $tokenId, string $nodeId, \DateTimeImmutable $fireAt): void
    {
        $now = new \DateTimeImmutable();
        $delayMs = max(0, ($fireAt->getTimestamp() - $now->getTimestamp()) * 1000);

        $this->messageBus->dispatch(
            new FireTimerMessage(
                processInstanceId: $processInstanceId,
                tokenId: $tokenId,
                nodeId: $nodeId,
            ),
            [new DelayStamp($delayMs)],
        );
    }
}
