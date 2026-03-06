<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\ProcessDefinition;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'BPMN process definition')]
final readonly class ProcessDefinitionDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Process definition UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Process name')]
        public string $name,
        #[OA\Property(description: 'Process description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Definition status', enum: ['draft', 'published', 'archived'])]
        public string $status,
        #[OA\Property(description: 'Creator user UUID', format: 'uuid')]
        public string $createdBy,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Last update timestamp', format: 'date-time', nullable: true)]
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
