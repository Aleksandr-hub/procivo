<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\Transition;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Workflow transition connecting two nodes')]
final readonly class TransitionDTO implements \JsonSerializable
{
    /**
     * @param array<int, array<string, mixed>>|null $formFields
     */
    public function __construct(
        #[OA\Property(description: 'Transition UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Process definition UUID', format: 'uuid')]
        public string $processDefinitionId,
        #[OA\Property(description: 'Source node UUID', format: 'uuid')]
        public string $sourceNodeId,
        #[OA\Property(description: 'Target node UUID', format: 'uuid')]
        public string $targetNodeId,
        #[OA\Property(description: 'Transition display name', nullable: true)]
        public ?string $name,
        #[OA\Property(description: 'Action key for user task transitions', nullable: true)]
        public ?string $actionKey,
        #[OA\Property(description: 'Condition expression for XOR gateways', nullable: true)]
        public ?string $conditionExpression,
        #[OA\Property(description: 'Form field definitions for this transition', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $formFields,
        #[OA\Property(description: 'Sort order for transition evaluation')]
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
