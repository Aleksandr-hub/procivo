<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Organization\Domain\Event\UserPermissionOverrideChangedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnUserPermissionOverrideChangedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(UserPermissionOverrideChangedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'permission.user_override_changed',
                actorId: $event->actorId,
                entityType: 'employee',
                entityId: $event->employeeId,
                organizationId: $event->organizationId,
                changes: [
                    'resource' => $event->resource,
                    'action' => $event->action,
                    'effect' => $event->effect,
                    'scope' => $event->scope,
                ],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
