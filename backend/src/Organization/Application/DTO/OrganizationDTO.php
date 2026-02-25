<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Organization;

final readonly class OrganizationDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $status,
        public string $ownerUserId,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(Organization $organization): self
    {
        return new self(
            id: $organization->id()->value(),
            name: $organization->name()->value(),
            slug: $organization->slug()->value(),
            description: $organization->description(),
            status: $organization->status()->value,
            ownerUserId: $organization->ownerUserId(),
            createdAt: $organization->createdAt()->value()->format(\DateTimeInterface::ATOM),
            updatedAt: $organization->updatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
