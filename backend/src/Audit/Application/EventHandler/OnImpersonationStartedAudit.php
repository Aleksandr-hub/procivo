<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Identity\Domain\Event\ImpersonationStartedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnImpersonationStartedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(ImpersonationStartedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'impersonation.started',
                actorId: $event->adminUserId,
                entityType: 'user',
                entityId: $event->targetUserId,
                organizationId: null,
                changes: [
                    'reason' => $event->reason,
                    'impersonated_by' => $event->adminUserId,
                ],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
