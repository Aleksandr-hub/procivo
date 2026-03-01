<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Workflow\Domain\Event\ProcessCancelledEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessCancelledAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(ProcessCancelledEvent $event): void
    {
        $changes = null !== $event->reason ? ['reason' => $event->reason] : null;

        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'process.cancelled',
                actorId: $event->cancelledBy,
                entityType: 'process_instance',
                entityId: $event->processInstanceId,
                organizationId: null, // Not available in this event; queryable via entity_id
                changes: $changes,
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
