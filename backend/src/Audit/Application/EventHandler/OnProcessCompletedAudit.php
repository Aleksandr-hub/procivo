<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Workflow\Domain\Event\ProcessCompletedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Audit handler for ProcessCompletedEvent.
 * Runs synchronously alongside OnSubProcessCompleted since ProcessCompletedEvent
 * is NOT routed to async transport (OnSubProcessCompleted must run synchronously
 * to continue parent process execution).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessCompletedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(ProcessCompletedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'process.completed',
                actorId: null, // System event — no user actor
                entityType: 'process_instance',
                entityId: $event->processInstanceId,
                organizationId: null, // Not available in this event; queryable via entity_id
                changes: null,
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
