<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Department;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Department resource within an organization')]
final readonly class DepartmentDTO
{
    public function __construct(
        #[OA\Property(description: 'Department UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Parent department UUID', format: 'uuid', nullable: true)]
        public ?string $parentId,
        #[OA\Property(description: 'Department name')]
        public string $name,
        #[OA\Property(description: 'Unique department code')]
        public string $code,
        #[OA\Property(description: 'Department description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Sort order within parent')]
        public int $sortOrder,
        #[OA\Property(description: 'Nesting level in hierarchy')]
        public int $level,
        #[OA\Property(description: 'Materialized path in hierarchy', example: '/root/engineering/')]
        public string $path,
        #[OA\Property(description: 'Department status', enum: ['active', 'archived'])]
        public string $status,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Department $department): self
    {
        return new self(
            id: $department->id()->value(),
            organizationId: $department->organizationId()->value(),
            parentId: $department->parentId()?->value(),
            name: $department->name(),
            code: $department->code()->value(),
            description: $department->description(),
            sortOrder: $department->sortOrder(),
            level: $department->level(),
            path: $department->path()->value(),
            status: $department->status()->value,
            createdAt: $department->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }
}
