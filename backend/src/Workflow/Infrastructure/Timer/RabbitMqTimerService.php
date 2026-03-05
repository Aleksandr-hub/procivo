<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Timer;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;

final readonly class RabbitMqTimerService implements TimerServiceInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private Connection $connection,
    ) {
    }

    public function scheduleTimer(string $processInstanceId, string $tokenId, string $nodeId, \DateTimeImmutable $fireAt): void
    {
        $now = new \DateTimeImmutable();

        $this->connection->insert('workflow_scheduled_timers', [
            'id' => Uuid::v4()->toRfc4122(),
            'process_instance_id' => $processInstanceId,
            'token_id' => $tokenId,
            'node_id' => $nodeId,
            'fire_at' => $fireAt->format('Y-m-d H:i:s.u'),
            'fired_at' => null,
            'dispatched_at' => null,
            'created_at' => $now->format('Y-m-d H:i:s.u'),
        ]);

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
