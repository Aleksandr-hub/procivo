<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\TokenId;

final readonly class WorkflowEngine
{
    public function __construct(
        private ExpressionEvaluator $expressionEvaluator,
    ) {
    }

    public function advanceToken(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph): void
    {
        $token = $instance->getToken($tokenId);
        if (!$token->isActive()) {
            return;
        }

        $nodeId = $token->nodeId()->value();
        $nodeType = $graph->nodeType($nodeId);

        match ($nodeType) {
            'start' => $this->handlePassThrough($instance, $tokenId, $graph, $nodeId),
            'end' => $this->handleEndNode($instance, $tokenId, $nodeId),
            'task' => $this->handleTaskNode($instance, $tokenId, $graph, $nodeId),
            'exclusive_gateway' => $this->handleExclusiveGateway($instance, $tokenId, $graph, $nodeId),
            'parallel_gateway' => $this->handleParallelGateway($instance, $tokenId, $graph, $nodeId),
            'inclusive_gateway' => $this->handleInclusiveGateway($instance, $tokenId, $graph, $nodeId),
            'timer' => $this->handleTimerNode($instance, $tokenId, $graph, $nodeId),
            'notification' => $this->handleNotificationNode($instance, $tokenId, $graph, $nodeId),
            'webhook' => $this->handleWebhookNode($instance, $tokenId, $graph, $nodeId),
            'sub_process' => $this->handleSubProcessNode($instance, $tokenId, $graph, $nodeId),
            default => throw WorkflowExecutionException::invalidTransition(\sprintf('Unsupported node type: %s', $nodeType)),
        };
    }

    public function resumeToken(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph): void
    {
        $this->moveTokenForward($instance, $tokenId, $graph);
    }

    public function executeAction(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $actionKey): void
    {
        $token = $instance->getToken($tokenId);
        if (!$token->isWaiting()) {
            throw WorkflowExecutionException::invalidTransition('Token is not waiting at a task node');
        }

        $nodeId = $token->nodeId()->value();
        $transition = $graph->findOutgoingTransitionByActionKey($nodeId, $actionKey);

        if (null === $transition) {
            $outgoing = $graph->outgoingTransitions($nodeId);
            if (1 === \count($outgoing)) {
                $transition = $outgoing[0];
            } else {
                throw WorkflowExecutionException::invalidTransition(\sprintf('No transition with actionKey "%s" found for node "%s"', $actionKey, $nodeId));
            }
        }

        $targetNodeId = NodeId::fromString($transition['target_node_id']);
        $instance->moveToken($tokenId, $token->nodeId(), $targetNodeId, $transition['id']);

        $this->advanceToken($instance, $tokenId, $graph);
    }

    private function handlePassThrough(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $this->moveTokenForward($instance, $tokenId, $graph);
    }

    private function handleEndNode(ProcessInstance $instance, TokenId $tokenId, string $nodeId): void
    {
        $instance->completeToken($tokenId, NodeId::fromString($nodeId));

        if ($instance->allTokensCompleted()) {
            $instance->complete();
        }
    }

    private function handleTaskNode(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $nodeName = $graph->nodeName($nodeId);
        $config = $graph->nodeConfig($nodeId);

        $instance->activateTaskNode(
            NodeId::fromString($nodeId),
            $tokenId,
            $nodeName,
            $config,
        );
    }

    private function handleExclusiveGateway(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $outgoing = $graph->outgoingTransitions($nodeId);
        if ([] === $outgoing) {
            throw WorkflowExecutionException::invalidTransition('Exclusive gateway has no outgoing transitions');
        }

        $variables = $instance->variables();
        $selectedTransition = null;
        $defaultTransition = null;
        $config = $graph->nodeConfig($nodeId);
        $defaultTransitionId = $config['default_transition_id'] ?? null;

        foreach ($outgoing as $transition) {
            if ($transition['id'] === $defaultTransitionId) {
                $defaultTransition = $transition;
                continue;
            }

            $condition = $transition['condition_expression'] ?? null;
            if (null === $condition || '' === $condition) {
                $selectedTransition = $transition;
                break;
            }

            if ($this->expressionEvaluator->evaluate($condition, $variables)) {
                $selectedTransition = $transition;
                break;
            }
        }

        $selectedTransition ??= $defaultTransition;

        if (null === $selectedTransition) {
            throw WorkflowExecutionException::invalidTransition(
                \sprintf('No matching transition found for exclusive gateway "%s" and no default branch configured', $nodeId),
            );
        }

        $instance->evaluateGateway(
            NodeId::fromString($nodeId),
            $tokenId,
            [$selectedTransition['id']],
        );

        $targetNodeId = NodeId::fromString($selectedTransition['target_node_id']);
        $instance->moveToken($tokenId, NodeId::fromString($nodeId), $targetNodeId, $selectedTransition['id']);

        $this->advanceToken($instance, $tokenId, $graph);
    }

    private function handleParallelGateway(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $incoming = $graph->incomingTransitions($nodeId);
        $outgoing = $graph->outgoingTransitions($nodeId);
        $nodeIdVO = NodeId::fromString($nodeId);

        if (\count($incoming) > 1) {
            $tokensAtNode = $instance->tokensAtNode($nodeIdVO);

            if (\count($tokensAtNode) < \count($incoming)) {
                return;
            }

            foreach ($tokensAtNode as $arrivedToken) {
                $instance->completeToken($arrivedToken->id(), $nodeIdVO);
            }
        } else {
            $instance->completeToken($tokenId, $nodeIdVO);
        }

        $selectedTransitionIds = array_map(static fn (array $t): string => $t['id'], $outgoing);
        $instance->evaluateGateway($nodeIdVO, $tokenId, $selectedTransitionIds);

        foreach ($outgoing as $transition) {
            $targetNodeId = NodeId::fromString($transition['target_node_id']);
            $newTokenId = $instance->createToken($targetNodeId);
            $this->advanceToken($instance, $newTokenId, $graph);
        }
    }

    private function handleInclusiveGateway(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $incoming = $graph->incomingTransitions($nodeId);
        $outgoing = $graph->outgoingTransitions($nodeId);
        $nodeIdVO = NodeId::fromString($nodeId);

        if (\count($incoming) > 1) {
            $tokensAtNode = $instance->tokensAtNode($nodeIdVO);

            if (\count($tokensAtNode) < \count($incoming)) {
                return;
            }

            foreach ($tokensAtNode as $arrivedToken) {
                $instance->completeToken($arrivedToken->id(), $nodeIdVO);
            }
        } else {
            $instance->completeToken($tokenId, $nodeIdVO);
        }

        $variables = $instance->variables();
        $selectedTransitions = [];
        $config = $graph->nodeConfig($nodeId);
        $defaultTransitionId = $config['default_transition_id'] ?? null;

        foreach ($outgoing as $transition) {
            if ($transition['id'] === $defaultTransitionId) {
                continue;
            }

            $condition = $transition['condition_expression'] ?? null;
            if (null === $condition || '' === $condition) {
                $selectedTransitions[] = $transition;
                continue;
            }

            if ($this->expressionEvaluator->evaluate($condition, $variables)) {
                $selectedTransitions[] = $transition;
            }
        }

        if ([] === $selectedTransitions && null !== $defaultTransitionId) {
            foreach ($outgoing as $transition) {
                if ($transition['id'] === $defaultTransitionId) {
                    $selectedTransitions[] = $transition;
                    break;
                }
            }
        }

        if ([] === $selectedTransitions) {
            throw WorkflowExecutionException::invalidTransition('No matching transition found for inclusive gateway');
        }

        $selectedTransitionIds = array_map(static fn (array $t): string => $t['id'], $selectedTransitions);
        $instance->evaluateGateway($nodeIdVO, $tokenId, $selectedTransitionIds);

        foreach ($selectedTransitions as $transition) {
            $targetNodeId = NodeId::fromString($transition['target_node_id']);
            $newTokenId = $instance->createToken($targetNodeId);
            $this->advanceToken($instance, $newTokenId, $graph);
        }
    }

    private function handleTimerNode(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $config = $graph->nodeConfig($nodeId);
        $fireAt = $this->calculateTimerFireAt($config);

        $instance->scheduleTimer(
            NodeId::fromString($nodeId),
            $tokenId,
            $fireAt,
        );
    }

    private function handleNotificationNode(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $config = $graph->nodeConfig($nodeId);
        $instance->activateNotificationNode(NodeId::fromString($nodeId), $tokenId, $config);
        $this->moveTokenForward($instance, $tokenId, $graph);
    }

    private function handleWebhookNode(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $config = $graph->nodeConfig($nodeId);
        $instance->activateWebhookNode(NodeId::fromString($nodeId), $tokenId, $config);
    }

    private function handleSubProcessNode(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $nodeId): void
    {
        $config = $graph->nodeConfig($nodeId);
        $instance->activateSubProcessNode(NodeId::fromString($nodeId), $tokenId, $config);
    }

    private function moveTokenForward(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph): void
    {
        $token = $instance->getToken($tokenId);
        $nodeId = $token->nodeId()->value();
        $outgoing = $graph->outgoingTransitions($nodeId);

        if ([] === $outgoing) {
            $instance->completeToken($tokenId, $token->nodeId());
            if ($instance->allTokensCompleted()) {
                $instance->complete();
            }

            return;
        }

        $transition = $outgoing[0];
        $targetNodeId = NodeId::fromString($transition['target_node_id']);
        $instance->moveToken($tokenId, $token->nodeId(), $targetNodeId, $transition['id']);

        $this->advanceToken($instance, $tokenId, $graph);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function calculateTimerFireAt(array $config): string
    {
        $timerType = (string) ($config['timer_type'] ?? 'duration');

        if ('duration' === $timerType) {
            $duration = (string) ($config['duration'] ?? 'PT1H');
            $interval = new \DateInterval($duration);

            return (new \DateTimeImmutable())->add($interval)->format('Y-m-d\TH:i:s.u');
        }

        if ('date' === $timerType) {
            return (string) ($config['date_expression'] ?? (new \DateTimeImmutable())->add(new \DateInterval('PT1H'))->format('Y-m-d\TH:i:s.u'));
        }

        return (new \DateTimeImmutable())->add(new \DateInterval('PT1H'))->format('Y-m-d\TH:i:s.u');
    }
}
