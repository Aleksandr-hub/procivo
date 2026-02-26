<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\ProcessDefinition;

final readonly class ProcessDefinitionDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public string $status,
        public string $createdBy,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(ProcessDefinition $entity): self
    {
        return new self(
            id: $entity->id()->value(),
            organizationId: $entity->organizationId(),
            name: $entity->name(),
            description: $entity->description(),
            status: $entity->status()->value,
            createdBy: $entity->createdBy(),
            createdAt: $entity->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $entity->updatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
