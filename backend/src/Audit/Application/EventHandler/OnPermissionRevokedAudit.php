<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Organization\Domain\Event\PermissionRevokedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnPermissionRevokedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(PermissionRevokedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'permission.revoked',
                actorId: null,
                entityType: 'role',
                entityId: $event->roleId,
                organizationId: null,
                changes: [
                    'permissionId' => $event->permissionId,
                ],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
