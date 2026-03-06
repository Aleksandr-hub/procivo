<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\Node;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'BPMN workflow node (start, end, task, gateway, timer)')]
final readonly class NodeDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        #[OA\Property(description: 'Node UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Process definition UUID', format: 'uuid')]
        public string $processDefinitionId,
        #[OA\Property(description: 'Node type', enum: ['start_event', 'end_event', 'user_task', 'exclusive_gateway', 'timer_event', 'sub_process'])]
        public string $type,
        #[OA\Property(description: 'Node display name')]
        public string $name,
        #[OA\Property(description: 'Node description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Node-specific configuration', type: 'object')]
        public array $config,
        #[OA\Property(description: 'Canvas X position')]
        public float $positionX,
        #[OA\Property(description: 'Canvas Y position')]
        public float $positionY,
    ) {
    }

    public static function fromEntity(Node $entity): self
    {
        return new self(
            id: $entity->id()->value(),
            processDefinitionId: $entity->processDefinitionId()->value(),
            type: $entity->type()->value,
            name: $entity->name(),
            description: $entity->description(),
            config: $entity->config(),
            positionX: $entity->positionX(),
            positionY: $entity->positionY(),
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
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'config' => $this->config,
            'position_x' => $this->positionX,
            'position_y' => $this->positionY,
        ];
    }
}
