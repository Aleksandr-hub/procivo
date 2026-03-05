<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Workflow\Domain\Event\ProcessCancelledEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessCancelledTimerCleanup
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function __invoke(ProcessCancelledEvent $event): void
    {
        $this->connection->executeStatement(
            'UPDATE workflow_scheduled_timers SET fired_at = NOW() WHERE process_instance_id = ? AND fired_at IS NULL',
            [$event->processInstanceId],
        );
    }
}
