<?php

declare(strict_types=1);

namespace App\Audit\Application\EventHandler;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\AuditLogId;
use App\TaskManager\Domain\Event\CommentAddedEvent;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnCommentAddedAudit
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function __invoke(CommentAddedEvent $event): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($event->taskId));
        $organizationId = null !== $task ? $task->organizationId() : null;

        $this->auditLogRepository->save(
            AuditLog::record(
                id: AuditLogId::generate(),
                eventType: 'task.comment_added',
                actorId: $event->authorId,
                entityType: 'task',
                entityId: $event->taskId,
                organizationId: $organizationId,
                changes: null, // Do not log comment content for privacy
                occurredAt: $event->occurredAt(),
            ),
        );
    }
}
