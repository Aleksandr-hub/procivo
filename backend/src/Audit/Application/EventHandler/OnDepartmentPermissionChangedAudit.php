<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Organization\Domain\Event\DepartmentPermissionChangedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnDepartmentPermissionChangedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(DepartmentPermissionChangedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'permission.department_changed',
                actorId: $event->actorId,
                entityType: 'department',
                entityId: $event->departmentId,
                organizationId: $event->organizationId,
                changes: [
                    'resource' => $event->resource,
                    'action' => $event->action,
                    'scope' => $event->scope,
                ],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
