<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;
use App\Workflow\Domain\Event\GatewayEvaluatedEvent;
use App\Workflow\Domain\Event\NotificationNodeActivatedEvent;
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
use App\Workflow\Domain\Event\VariablesMergedEvent;
use App\Workflow\Domain\Event\SubProcessCompletedEvent;
use App\Workflow\Domain\Event\SubProcessNodeActivatedEvent;
use App\Workflow\Domain\Event\WebhookFiredEvent;
use App\Workflow\Domain\Event\WebhookNodeActivatedEvent;

final class EventSerializer
{
    /**
     * @return array{event_type: string, payload: array<string, mixed>}
     */
    public function serialize(DomainEvent $event): array
    {
        $eventType = $event->eventName();
        $payload = match (true) {
            $event instanceof ProcessStartedEvent => array_filter([
                'process_instance_id' => $event->processInstanceId,
                'process_definition_id' => $event->processDefinitionId,
                'version_id' => $event->versionId,
                'organization_id' => $event->organizationId,
                'started_by' => $event->startedBy,
                'variables' => $event->variables,
                'parent_process_instance_id' => $event->parentProcessInstanceId,
                'parent_token_id' => $event->parentTokenId,
            ], static fn (mixed $v): bool => null !== $v),
            $event instanceof TokenCreatedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'token_id' => $event->tokenId,
                'node_id' => $event->nodeId,
            ],
            $event instanceof TokenMovedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'token_id' => $event->tokenId,
                'from_node_id' => $event->fromNodeId,
                'to_node_id' => $event->toNodeId,
                'transition_id' => $event->transitionId,
            ],
            $event instanceof TokenCompletedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'token_id' => $event->tokenId,
                'node_id' => $event->nodeId,
            ],
            $event instanceof TaskNodeActivatedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'organization_id' => $event->organizationId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'node_name' => $event->nodeName,
                'task_config' => $event->taskConfig,
            ],
            $event instanceof GatewayEvaluatedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'selected_transition_ids' => $event->selectedTransitionIds,
            ],
            $event instanceof TimerScheduledEvent => [
                'process_instance_id' => $event->processInstanceId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'fire_at' => $event->fireAt,
            ],
            $event instanceof TimerFiredEvent => [
                'process_instance_id' => $event->processInstanceId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
            ],
            $event instanceof ProcessCompletedEvent => [
                'process_instance_id' => $event->processInstanceId,
            ],
            $event instanceof VariablesMergedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'node_id' => $event->nodeId,
                'action_key' => $event->actionKey,
                'merged_data' => $event->mergedData,
            ],
            $event instanceof ProcessCancelledEvent => [
                'process_instance_id' => $event->processInstanceId,
                'cancelled_by' => $event->cancelledBy,
                'reason' => $event->reason,
            ],
            $event instanceof NotificationNodeActivatedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'organization_id' => $event->organizationId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'notification_config' => $event->notificationConfig,
                'variables' => $event->variables,
                'started_by' => $event->startedBy,
            ],
            $event instanceof WebhookNodeActivatedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'organization_id' => $event->organizationId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'webhook_config' => $event->webhookConfig,
                'variables' => $event->variables,
            ],
            $event instanceof WebhookFiredEvent => [
                'process_instance_id' => $event->processInstanceId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
            ],
            $event instanceof SubProcessNodeActivatedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'organization_id' => $event->organizationId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'sub_process_config' => $event->subProcessConfig,
                'variables' => $event->variables,
                'started_by' => $event->startedBy,
            ],
            $event instanceof SubProcessCompletedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'node_id' => $event->nodeId,
                'token_id' => $event->tokenId,
                'child_process_instance_id' => $event->childProcessInstanceId,
            ],
            $event instanceof ProcessInstanceMigratedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'from_version_id' => $event->fromVersionId,
                'to_version_id' => $event->toVersionId,
                'migrated_by' => $event->migratedBy,
            ],
            default => throw new \RuntimeException(\sprintf('Unknown event type: %s', $event::class)),
        };

        return ['event_type' => $eventType, 'payload' => $payload];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function deserialize(string $eventType, array $payload, \DateTimeImmutable $occurredAt): DomainEvent
    {
        return match ($eventType) {
            'workflow.process.started' => new ProcessStartedEvent(
                processInstanceId: $payload['process_instance_id'],
                processDefinitionId: $payload['process_definition_id'],
                versionId: $payload['version_id'],
                organizationId: $payload['organization_id'],
                startedBy: $payload['started_by'],
                variables: $payload['variables'] ?? [],
                parentProcessInstanceId: $payload['parent_process_instance_id'] ?? null,
                parentTokenId: $payload['parent_token_id'] ?? null,
                occurredAt: $occurredAt,
            ),
            'workflow.token.created' => new TokenCreatedEvent(
                processInstanceId: $payload['process_instance_id'],
                tokenId: $payload['token_id'],
                nodeId: $payload['node_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.token.moved' => new TokenMovedEvent(
                processInstanceId: $payload['process_instance_id'],
                tokenId: $payload['token_id'],
                fromNodeId: $payload['from_node_id'],
                toNodeId: $payload['to_node_id'],
                transitionId: $payload['transition_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.token.completed' => new TokenCompletedEvent(
                processInstanceId: $payload['process_instance_id'],
                tokenId: $payload['token_id'],
                nodeId: $payload['node_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.task_node.activated' => new TaskNodeActivatedEvent(
                processInstanceId: $payload['process_instance_id'],
                organizationId: $payload['organization_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                nodeName: $payload['node_name'],
                taskConfig: $payload['task_config'] ?? [],
                occurredAt: $occurredAt,
            ),
            'workflow.gateway.evaluated' => new GatewayEvaluatedEvent(
                processInstanceId: $payload['process_instance_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                selectedTransitionIds: $payload['selected_transition_ids'] ?? [],
                occurredAt: $occurredAt,
            ),
            'workflow.timer.scheduled' => new TimerScheduledEvent(
                processInstanceId: $payload['process_instance_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                fireAt: $payload['fire_at'],
                occurredAt: $occurredAt,
            ),
            'workflow.timer.fired' => new TimerFiredEvent(
                processInstanceId: $payload['process_instance_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.process.variables.merged' => new VariablesMergedEvent(
                processInstanceId: $payload['process_instance_id'],
                nodeId: $payload['node_id'],
                actionKey: $payload['action_key'],
                mergedData: $payload['merged_data'] ?? [],
                occurredAt: $occurredAt,
            ),
            'workflow.process.completed' => new ProcessCompletedEvent(
                processInstanceId: $payload['process_instance_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.process.cancelled' => new ProcessCancelledEvent(
                processInstanceId: $payload['process_instance_id'],
                cancelledBy: $payload['cancelled_by'],
                reason: $payload['reason'] ?? null,
                occurredAt: $occurredAt,
            ),
            'workflow.notification_node.activated' => new NotificationNodeActivatedEvent(
                processInstanceId: $payload['process_instance_id'],
                organizationId: $payload['organization_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                notificationConfig: $payload['notification_config'] ?? [],
                variables: $payload['variables'] ?? [],
                startedBy: $payload['started_by'] ?? '',
                occurredAt: $occurredAt,
            ),
            'workflow.webhook_node.activated' => new WebhookNodeActivatedEvent(
                processInstanceId: $payload['process_instance_id'],
                organizationId: $payload['organization_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                webhookConfig: $payload['webhook_config'] ?? [],
                variables: $payload['variables'] ?? [],
                occurredAt: $occurredAt,
            ),
            'workflow.webhook.fired' => new WebhookFiredEvent(
                processInstanceId: $payload['process_instance_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.sub_process_node.activated' => new SubProcessNodeActivatedEvent(
                processInstanceId: $payload['process_instance_id'],
                organizationId: $payload['organization_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                subProcessConfig: $payload['sub_process_config'] ?? [],
                variables: $payload['variables'] ?? [],
                startedBy: $payload['started_by'] ?? '',
                occurredAt: $occurredAt,
            ),
            'workflow.sub_process.completed' => new SubProcessCompletedEvent(
                processInstanceId: $payload['process_instance_id'],
                nodeId: $payload['node_id'],
                tokenId: $payload['token_id'],
                childProcessInstanceId: $payload['child_process_instance_id'],
                occurredAt: $occurredAt,
            ),
            'workflow.process_instance.migrated' => new ProcessInstanceMigratedEvent(
                processInstanceId: $payload['process_instance_id'],
                fromVersionId: $payload['from_version_id'],
                toVersionId: $payload['to_version_id'],
                migratedBy: $payload['migrated_by'],
                occurredAt: $occurredAt,
            ),
            default => throw new \RuntimeException(\sprintf('Unknown event type: %s', $eventType)),
        };
    }
}
