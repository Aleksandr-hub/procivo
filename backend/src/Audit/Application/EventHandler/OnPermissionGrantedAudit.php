<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Organization\Domain\Event\PermissionGrantedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnPermissionGrantedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(PermissionGrantedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'permission.granted',
                actorId: null,
                entityType: 'role',
                entityId: $event->roleId,
                organizationId: null,
                changes: [
                    'permissionId' => $event->permissionId,
                    'resource' => $event->resource,
                    'action' => $event->action,
                    'scope' => $event->scope,
                ],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
