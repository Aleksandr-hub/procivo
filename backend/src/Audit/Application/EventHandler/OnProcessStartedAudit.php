<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Workflow\Domain\Event\ProcessStartedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessStartedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(ProcessStartedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'process.started',
                actorId: $event->startedBy,
                entityType: 'process_instance',
                entityId: $event->processInstanceId,
                organizationId: $event->organizationId,
                changes: null,
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
