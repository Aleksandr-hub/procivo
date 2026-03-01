<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\ReadModel;

use App\Workflow\Domain\Event\ProcessCancelledEvent;
use App\Workflow\Domain\Event\ProcessCompletedEvent;
use App\Workflow\Domain\Event\ProcessInstanceMigratedEvent;
use App\Workflow\Domain\Event\ProcessStartedEvent;
use App\Workflow\Domain\Event\TaskNodeActivatedEvent;
use App\Workflow\Domain\Event\TimerFiredEvent;
use App\Workflow\Domain\Event\TimerScheduledEvent;
use App\Workflow\Domain\Event\TokenCompletedEvent;
use App\Workflow\Domain\Event\TokenCreatedEvent;
use App\Workflow\Domain\Event\TokenMovedEvent;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class ProcessInstanceProjection
{
    private const string TABLE = 'workflow_process_instances_view';

    public function __construct(
        private Connection $connection,
        private ProcessDefinitionRepositoryInterface $definitionRepository,
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProcessStarted(ProcessStartedEvent $event): void
    {
        $definitionName = '';
        $definition = $this->definitionRepository->findById(
            ProcessDefinitionId::fromString($event->processDefinitionId),
        );
        if (null !== $definition) {
            $definitionName = $definition->name();
        }

        $this->connection->insert(self::TABLE, [
            'id' => $event->processInstanceId,
            'definition_id' => $event->processDefinitionId,
            'definition_name' => $definitionName,
            'version_id' => $event->versionId,
            'organization_id' => $event->organizationId,
            'status' => 'running',
            'started_by' => $event->startedBy,
            'variables' => json_encode($event->variables, \JSON_THROW_ON_ERROR),
            'tokens' => json_encode([], \JSON_THROW_ON_ERROR),
            'started_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTokenCreated(TokenCreatedEvent $event): void
    {
        $this->updateToken($event->processInstanceId, $event->tokenId, [
            'id' => $event->tokenId,
            'node_id' => $event->nodeId,
            'status' => 'active',
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTokenMoved(TokenMovedEvent $event): void
    {
        $this->updateToken($event->processInstanceId, $event->tokenId, [
            'node_id' => $event->toNodeId,
            'status' => 'active',
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTokenCompleted(TokenCompletedEvent $event): void
    {
        $this->updateToken($event->processInstanceId, $event->tokenId, [
            'status' => 'completed',
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskNodeActivated(TaskNodeActivatedEvent $event): void
    {
        $this->updateToken($event->processInstanceId, $event->tokenId, [
            'status' => 'waiting',
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTimerScheduled(TimerScheduledEvent $event): void
    {
        $this->updateToken($event->processInstanceId, $event->tokenId, [
            'status' => 'waiting',
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTimerFired(TimerFiredEvent $event): void
    {
        $this->updateToken($event->processInstanceId, $event->tokenId, [
            'status' => 'active',
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProcessCompleted(ProcessCompletedEvent $event): void
    {
        $this->connection->update(self::TABLE, [
            'status' => 'completed',
            'completed_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
        ], ['id' => $event->processInstanceId]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProcessCancelled(ProcessCancelledEvent $event): void
    {
        $this->connection->update(self::TABLE, [
            'status' => 'cancelled',
            'cancelled_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
        ], ['id' => $event->processInstanceId]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onProcessInstanceMigrated(ProcessInstanceMigratedEvent $event): void
    {
        $this->connection->update(self::TABLE, [
            'version_id' => $event->toVersionId,
        ], ['id' => $event->processInstanceId]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateToken(string $processInstanceId, string $tokenId, array $data): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT tokens FROM ' . self::TABLE . ' WHERE id = ?',
            [$processInstanceId],
        );

        if (false === $row) {
            return;
        }

        /** @var array<string, array<string, mixed>> $tokens */
        $tokens = json_decode((string) $row['tokens'], true, 512, \JSON_THROW_ON_ERROR);
        $tokens[$tokenId] = array_merge($tokens[$tokenId] ?? [], $data);

        $this->connection->update(self::TABLE, [
            'tokens' => json_encode($tokens, \JSON_THROW_ON_ERROR),
        ], ['id' => $processInstanceId]);
    }
}
