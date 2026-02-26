<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

use App\Workflow\Domain\ValueObject\ConditionExpression;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\TransitionId;

class Transition
{
    private string $id;
    private string $processDefinitionId;
    private string $sourceNodeId;
    private string $targetNodeId;
    private ?string $name;
    private ?string $actionKey;
    private ?string $conditionExpression;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $formFields;
    private int $sortOrder;

    private function __construct()
    {
    }

    /**
     * @param array<int, array<string, mixed>>|null $formFields
     */
    public static function create(
        TransitionId $id,
        ProcessDefinitionId $processDefinitionId,
        NodeId $sourceNodeId,
        NodeId $targetNodeId,
        ?string $name = null,
        ?string $actionKey = null,
        ?ConditionExpression $conditionExpression = null,
        ?array $formFields = null,
        int $sortOrder = 0,
    ): self {
        $transition = new self();
        $transition->id = $id->value();
        $transition->processDefinitionId = $processDefinitionId->value();
        $transition->sourceNodeId = $sourceNodeId->value();
        $transition->targetNodeId = $targetNodeId->value();
        $transition->name = $name;
        $transition->actionKey = $actionKey;
        $transition->conditionExpression = $conditionExpression?->value();
        $transition->formFields = $formFields;
        $transition->sortOrder = $sortOrder;

        return $transition;
    }

    /**
     * @param array<int, array<string, mixed>>|null $formFields
     */
    public function update(
        ?string $name,
        ?string $actionKey,
        ?ConditionExpression $conditionExpression,
        ?array $formFields,
        int $sortOrder,
    ): void {
        $this->name = $name;
        $this->actionKey = $actionKey;
        $this->conditionExpression = $conditionExpression?->value();
        $this->formFields = $formFields;
        $this->sortOrder = $sortOrder;
    }

    public function id(): TransitionId
    {
        return TransitionId::fromString($this->id);
    }

    public function processDefinitionId(): ProcessDefinitionId
    {
        return ProcessDefinitionId::fromString($this->processDefinitionId);
    }

    public function sourceNodeId(): NodeId
    {
        return NodeId::fromString($this->sourceNodeId);
    }

    public function targetNodeId(): NodeId
    {
        return NodeId::fromString($this->targetNodeId);
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function actionKey(): ?string
    {
        return $this->actionKey;
    }

    public function conditionExpression(): ?ConditionExpression
    {
        return null !== $this->conditionExpression
            ? ConditionExpression::fromString($this->conditionExpression)
            : null;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function formFields(): ?array
    {
        return $this->formFields;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'source_node_id' => $this->sourceNodeId,
            'target_node_id' => $this->targetNodeId,
            'name' => $this->name,
            'action_key' => $this->actionKey,
            'condition_expression' => $this->conditionExpression,
            'form_fields' => $this->formFields,
            'sort_order' => $this->sortOrder,
        ];
    }
}
