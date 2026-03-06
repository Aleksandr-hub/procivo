<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Position;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Position within a department')]
final readonly class PositionDTO
{
    public function __construct(
        #[OA\Property(description: 'Position UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Department UUID', format: 'uuid')]
        public string $departmentId,
        #[OA\Property(description: 'Position name')]
        public string $name,
        #[OA\Property(description: 'Position description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Sort order within department')]
        public int $sortOrder,
        #[OA\Property(description: 'Whether this position is the department head')]
        public bool $isHead,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Position $position): self
    {
        return new self(
            id: $position->id()->value(),
            organizationId: $position->organizationId()->value(),
            departmentId: $position->departmentId()->value(),
            name: $position->name()->value(),
            description: $position->description(),
            sortOrder: $position->sortOrder(),
            isHead: $position->isHead(),
            createdAt: $position->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }
}
