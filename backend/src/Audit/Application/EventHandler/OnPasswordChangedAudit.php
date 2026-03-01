<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Identity\Domain\Event\PasswordChangedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnPasswordChangedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(PasswordChangedEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'user.password_changed',
                actorId: $event->userId,
                entityType: 'user',
                entityId: $event->userId,
                organizationId: null,
                changes: null, // Never log password data
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
