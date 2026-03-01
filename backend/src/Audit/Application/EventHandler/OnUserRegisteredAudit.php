<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\Identity\Domain\Event\UserRegisteredEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnUserRegisteredAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'user.registered',
                actorId: $event->userId, // Self-registration: actor is the new user
                entityType: 'user',
                entityId: $event->userId,
                organizationId: null, // User doesn't belong to org at registration time
                changes: ['email' => $event->email],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
