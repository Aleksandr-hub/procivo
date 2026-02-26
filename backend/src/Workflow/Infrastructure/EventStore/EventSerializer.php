<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;
use App\Workflow\Domain\Event\GatewayEvaluatedEvent;
use App\Workflow\Domain\Event\ProcessCancelledEvent;
use App\Workflow\Domain\Event\ProcessCompletedEvent;
use App\Workflow\Domain\Event\ProcessStartedEvent;
use App\Workflow\Domain\Event\TaskNodeActivatedEvent;
use App\Workflow\Domain\Event\TimerFiredEvent;
use App\Workflow\Domain\Event\TimerScheduledEvent;
use App\Workflow\Domain\Event\TokenCompletedEvent;
use App\Workflow\Domain\Event\TokenCreatedEvent;
use App\Workflow\Domain\Event\TokenMovedEvent;
use App\Workflow\Domain\Event\VariablesMergedEvent;

final class EventSerializer
{
    /**
     * @return array{event_type: string, payload: array<string, mixed>}
     */
    public function serialize(DomainEvent $event): array
    {
        $eventType = $event->eventName();
        $payload = match (true) {
            $event instanceof ProcessStartedEvent => [
                'process_instance_id' => $event->processInstanceId,
                'process_definition_id' => $event->processDefinitionId,
                'version_id' => $event->versionId,
                'organization_id' => $event->organizationId,
                'started_by' => $event->startedBy,
                'variables' => $event->variables,
            ],
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
            default => throw new \RuntimeException(\sprintf('Unknown event type: %s', $eventType)),
        };
    }
}
