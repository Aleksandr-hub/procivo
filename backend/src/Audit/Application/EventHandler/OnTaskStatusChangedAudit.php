<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\TaskManager\Domain\Event\TaskStatusChangedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskStatusChangedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(TaskStatusChangedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));
        $organizationId = null !== $task ? $task->organizationId() : null;

        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'task.status_changed',
                actorId: $event->actorId,
                entityType: 'task',
                entityId: $event->taskId,
                organizationId: $organizationId,
                changes: ['from' => $event->oldStatus, 'to' => $event->newStatus],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
