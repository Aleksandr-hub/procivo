<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\NodeType;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;

class Node
{
    private string $id;
    private string $processDefinitionId;
    private string $type;
    private string $name;
    private ?string $description;
    /** @var array<string, mixed> */
    private array $config;
    private float $positionX;
    private float $positionY;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function create(
        NodeId $id,
        ProcessDefinitionId $processDefinitionId,
        NodeType $type,
        string $name,
        ?string $description = null,
        array $config = [],
        float $positionX = 0.0,
        float $positionY = 0.0,
    ): self {
        $node = new self();
        $node->id = $id->value();
        $node->processDefinitionId = $processDefinitionId->value();
        $node->type = $type->value;
        $node->name = $name;
        $node->description = $description;
        $node->config = $config;
        $node->positionX = $positionX;
        $node->positionY = $positionY;

        return $node;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function update(
        string $name,
        ?string $description,
        array $config,
        float $positionX,
        float $positionY,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->config = $config;
        $this->positionX = $positionX;
        $this->positionY = $positionY;
    }

    public function id(): NodeId
    {
        return NodeId::fromString($this->id);
    }

    public function processDefinitionId(): ProcessDefinitionId
    {
        return ProcessDefinitionId::fromString($this->processDefinitionId);
    }

    public function type(): NodeType
    {
        return NodeType::from($this->type);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function positionX(): float
    {
        return $this->positionX;
    }

    public function positionY(): float
    {
        return $this->positionY;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'config' => $this->config,
            'position_x' => $this->positionX,
            'position_y' => $this->positionY,
        ];
    }
}
