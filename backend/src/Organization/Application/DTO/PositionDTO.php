<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Position;

final readonly class PositionDTO
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $departmentId,
        public string $name,
        public ?string $description,
        public int $sortOrder,
        public bool $isHead,
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
