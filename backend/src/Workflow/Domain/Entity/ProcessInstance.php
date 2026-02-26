<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

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
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\ProcessInstanceStatus;
use App\Workflow\Domain\ValueObject\TokenId;

class ProcessInstance
{
    private ProcessInstanceId $id;
    private ProcessDefinitionId $processDefinitionId;
    private ProcessDefinitionVersionId $versionId;
    private string $organizationId;
    private ProcessInstanceStatus $status;
    private string $startedBy;
    /** @var array<string, mixed> */
    private array $variables;
    /** @var array<string, Token> */
    private array $tokens = [];
    private int $version = 0;

    /** @var list<DomainEvent> */
    private array $uncommittedEvents = [];

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $variables
     */
    public static function start(
        ProcessInstanceId $id,
        ProcessDefinitionId $processDefinitionId,
        ProcessDefinitionVersionId $versionId,
        string $organizationId,
        string $startedBy,
        array $variables,
        NodeId $startNodeId,
    ): self {
        $instance = new self();

        $instance->recordThat(new ProcessStartedEvent(
            processInstanceId: $id->value(),
            processDefinitionId: $processDefinitionId->value(),
            versionId: $versionId->value(),
            organizationId: $organizationId,
            startedBy: $startedBy,
            variables: $variables,
        ));

        $tokenId = TokenId::generate();
        $instance->recordThat(new TokenCreatedEvent(
            processInstanceId: $id->value(),
            tokenId: $tokenId->value(),
            nodeId: $startNodeId->value(),
        ));

        return $instance;
    }

    /**
     * @param list<DomainEvent> $events
     */
    public static function reconstitute(array $events): self
    {
        $instance = new self();

        foreach ($events as $event) {
            $instance->apply($event);
            ++$instance->version;
        }

        return $instance;
    }

    public function createToken(NodeId $nodeId): TokenId
    {
        $tokenId = TokenId::generate();
        $this->recordThat(new TokenCreatedEvent(
            processInstanceId: $this->id->value(),
            tokenId: $tokenId->value(),
            nodeId: $nodeId->value(),
        ));

        return $tokenId;
    }

    public function moveToken(TokenId $tokenId, NodeId $fromNodeId, NodeId $toNodeId, string $transitionId): void
    {
        $this->recordThat(new TokenMovedEvent(
            processInstanceId: $this->id->value(),
            tokenId: $tokenId->value(),
            fromNodeId: $fromNodeId->value(),
            toNodeId: $toNodeId->value(),
            transitionId: $transitionId,
        ));
    }

    public function completeToken(TokenId $tokenId, NodeId $nodeId): void
    {
        $this->recordThat(new TokenCompletedEvent(
            processInstanceId: $this->id->value(),
            tokenId: $tokenId->value(),
            nodeId: $nodeId->value(),
        ));
    }

    /**
     * @param array<string, mixed> $taskConfig
     */
    public function activateTaskNode(NodeId $nodeId, TokenId $tokenId, string $nodeName, array $taskConfig): void
    {
        $this->recordThat(new TaskNodeActivatedEvent(
            processInstanceId: $this->id->value(),
            organizationId: $this->organizationId,
            nodeId: $nodeId->value(),
            tokenId: $tokenId->value(),
            nodeName: $nodeName,
            taskConfig: $taskConfig,
        ));
    }

    /**
     * @param list<string> $selectedTransitionIds
     */
    public function evaluateGateway(NodeId $nodeId, TokenId $tokenId, array $selectedTransitionIds): void
    {
        $this->recordThat(new GatewayEvaluatedEvent(
            processInstanceId: $this->id->value(),
            nodeId: $nodeId->value(),
            tokenId: $tokenId->value(),
            selectedTransitionIds: $selectedTransitionIds,
        ));
    }

    public function scheduleTimer(NodeId $nodeId, TokenId $tokenId, string $fireAt): void
    {
        $this->recordThat(new TimerScheduledEvent(
            processInstanceId: $this->id->value(),
            nodeId: $nodeId->value(),
            tokenId: $tokenId->value(),
            fireAt: $fireAt,
        ));
    }

    public function fireTimer(NodeId $nodeId, TokenId $tokenId): void
    {
        $this->recordThat(new TimerFiredEvent(
            processInstanceId: $this->id->value(),
            nodeId: $nodeId->value(),
            tokenId: $tokenId->value(),
        ));
    }

    public function complete(): void
    {
        $this->recordThat(new ProcessCompletedEvent(
            processInstanceId: $this->id->value(),
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function mergeVariables(string $nodeId, string $actionKey, array $data): void
    {
        if ([] === $data) {
            return;
        }

        $this->recordThat(new VariablesMergedEvent(
            processInstanceId: $this->id->value(),
            nodeId: $nodeId,
            actionKey: $actionKey,
            mergedData: $data,
        ));
    }

    public function cancel(string $cancelledBy, ?string $reason = null): void
    {
        if (!$this->isRunning()) {
            throw WorkflowExecutionException::processNotRunning($this->id->value());
        }

        $this->recordThat(new ProcessCancelledEvent(
            processInstanceId: $this->id->value(),
            cancelledBy: $cancelledBy,
            reason: $reason,
        ));
    }

    public function getToken(TokenId $tokenId): Token
    {
        $key = $tokenId->value();
        if (!isset($this->tokens[$key])) {
            throw WorkflowExecutionException::tokenNotFound($key);
        }

        return $this->tokens[$key];
    }

    /**
     * @return list<Token>
     */
    public function activeTokens(): array
    {
        return array_values(array_filter(
            $this->tokens,
            static fn (Token $t) => $t->isActive() || $t->isWaiting(),
        ));
    }

    /**
     * @return list<Token>
     */
    public function tokensAtNode(NodeId $nodeId): array
    {
        return array_values(array_filter(
            $this->tokens,
            static fn (Token $t) => $t->nodeId()->equals($nodeId) && ($t->isActive() || $t->isWaiting()),
        ));
    }

    public function allTokensCompleted(): bool
    {
        foreach ($this->tokens as $token) {
            if ($token->isActive() || $token->isWaiting()) {
                return false;
            }
        }

        return true;
    }

    public function id(): ProcessInstanceId
    {
        return $this->id;
    }

    public function processDefinitionId(): ProcessDefinitionId
    {
        return $this->processDefinitionId;
    }

    public function versionId(): ProcessDefinitionVersionId
    {
        return $this->versionId;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function status(): ProcessInstanceStatus
    {
        return $this->status;
    }

    public function startedBy(): string
    {
        return $this->startedBy;
    }

    /**
     * @return array<string, mixed>
     */
    public function variables(): array
    {
        return $this->variables;
    }

    public function isRunning(): bool
    {
        return ProcessInstanceStatus::Running === $this->status;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return list<DomainEvent>
     */
    public function uncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    public function clearUncommittedEvents(): void
    {
        $this->uncommittedEvents = [];
    }

    private function recordThat(DomainEvent $event): void
    {
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
    }

    private function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof ProcessStartedEvent => $this->applyProcessStarted($event),
            $event instanceof TokenCreatedEvent => $this->applyTokenCreated($event),
            $event instanceof TokenMovedEvent => $this->applyTokenMoved($event),
            $event instanceof TokenCompletedEvent => $this->applyTokenCompleted($event),
            $event instanceof TaskNodeActivatedEvent => $this->applyTaskNodeActivated($event),
            $event instanceof TimerScheduledEvent => $this->applyTimerScheduled($event),
            $event instanceof TimerFiredEvent => $this->applyTimerFired($event),
            $event instanceof VariablesMergedEvent => $this->applyVariablesMerged($event),
            $event instanceof GatewayEvaluatedEvent => null, // informational, no state change
            $event instanceof ProcessCompletedEvent => $this->applyProcessCompleted(),
            $event instanceof ProcessCancelledEvent => $this->applyProcessCancelled(),
            default => null,
        };
    }

    private function applyProcessStarted(ProcessStartedEvent $event): void
    {
        $this->id = ProcessInstanceId::fromString($event->processInstanceId);
        $this->processDefinitionId = ProcessDefinitionId::fromString($event->processDefinitionId);
        $this->versionId = ProcessDefinitionVersionId::fromString($event->versionId);
        $this->organizationId = $event->organizationId;
        $this->status = ProcessInstanceStatus::Running;
        $this->startedBy = $event->startedBy;
        $this->variables = $event->variables;
    }

    private function applyTokenCreated(TokenCreatedEvent $event): void
    {
        $tokenId = TokenId::fromString($event->tokenId);
        $nodeId = NodeId::fromString($event->nodeId);
        $this->tokens[$event->tokenId] = Token::create($tokenId, $nodeId);
    }

    private function applyTokenMoved(TokenMovedEvent $event): void
    {
        $token = $this->tokens[$event->tokenId] ?? null;
        $token?->moveTo(NodeId::fromString($event->toNodeId));
    }

    private function applyTokenCompleted(TokenCompletedEvent $event): void
    {
        $token = $this->tokens[$event->tokenId] ?? null;
        $token?->complete();
    }

    private function applyTaskNodeActivated(TaskNodeActivatedEvent $event): void
    {
        $token = $this->tokens[$event->tokenId] ?? null;
        $token?->wait();
    }

    private function applyTimerScheduled(TimerScheduledEvent $event): void
    {
        $token = $this->tokens[$event->tokenId] ?? null;
        $token?->wait();
    }

    private function applyTimerFired(TimerFiredEvent $event): void
    {
        $token = $this->tokens[$event->tokenId] ?? null;
        $token?->activate();
    }

    private function applyVariablesMerged(VariablesMergedEvent $event): void
    {
        $this->variables = array_merge($this->variables, $event->mergedData);
    }

    private function applyProcessCompleted(): void
    {
        $this->status = ProcessInstanceStatus::Completed;
    }

    private function applyProcessCancelled(): void
    {
        $this->status = ProcessInstanceStatus::Cancelled;
        foreach ($this->tokens as $token) {
            if ($token->isActive() || $token->isWaiting()) {
                $token->cancel();
            }
        }
    }
}
