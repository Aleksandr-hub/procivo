<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\Transition;

final readonly class TransitionDTO implements \JsonSerializable
{
    /**
     * @param array<int, array<string, mixed>>|null $formFields
     */
    public function __construct(
        public string $id,
        public string $processDefinitionId,
        public string $sourceNodeId,
        public string $targetNodeId,
        public ?string $name,
        public ?string $actionKey,
        public ?string $conditionExpression,
        public ?array $formFields,
        public int $sortOrder,
    ) {
    }

    public static function fromEntity(Transition $entity): self
    {
        return new self(
            id: $entity->id()->value(),
            processDefinitionId: $entity->processDefinitionId()->value(),
            sourceNodeId: $entity->sourceNodeId()->value(),
            targetNodeId: $entity->targetNodeId()->value(),
            name: $entity->name(),
            actionKey: $entity->actionKey(),
            conditionExpression: $entity->conditionExpression()?->value(),
            formFields: $entity->formFields(),
            sortOrder: $entity->sortOrder(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'process_definition_id' => $this->processDefinitionId,
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
