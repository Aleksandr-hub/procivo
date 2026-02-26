<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Workflow\Domain\Event\TimerScheduledEvent;
use App\Workflow\Infrastructure\Timer\TimerServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTimerScheduled
{
    public function __construct(
        private TimerServiceInterface $timerService,
    ) {
    }

    public function __invoke(TimerScheduledEvent $event): void
    {
        $fireAt = new \DateTimeImmutable($event->fireAt);

        $this->timerService->scheduleTimer(
            processInstanceId: $event->processInstanceId,
            tokenId: $event->tokenId,
            nodeId: $event->nodeId,
            fireAt: $fireAt,
        );
    }
}
