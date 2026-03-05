<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Identity\Domain\Event\ImpersonationEndedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnImpersonationEndedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(ImpersonationEndedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'impersonation.ended',
                actorId: $event->adminUserId,
                entityType: 'user',
                entityId: $event->adminUserId,
                organizationId: null,
                changes: null,
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
