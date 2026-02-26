<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\Node;

final readonly class NodeDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        public string $id,
        public string $processDefinitionId,
        public string $type,
        public string $name,
        public ?string $description,
        public array $config,
        public float $positionX,
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
