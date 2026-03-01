<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\TaskManager\Domain\Event\TaskAssignedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskAssignedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(TaskAssignedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));
        $organizationId = null !== $task ? $task->organizationId() : null;

        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'task.assigned',
                actorId: $event->actorId,
                entityType: 'task',
                entityId: $event->taskId,
                organizationId: $organizationId,
                changes: ['assignee_id' => $event->assigneeId],
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
