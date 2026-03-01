<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\TaskManager\Domain\Event\TaskDeletedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskDeletedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(TaskDeletedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'task.deleted',
                actorId: $event->actorId,
                entityType: 'task',
                entityId: $event->taskId,
                organizationId: $event->organizationId,
                changes: null,
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
