<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Organization;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Organization resource')]
final readonly class OrganizationDTO
{
    public function __construct(
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization name')]
        public string $name,
        #[OA\Property(description: 'URL-friendly slug')]
        public string $slug,
        #[OA\Property(description: 'Organization description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Organization status', enum: ['active', 'suspended'])]
        public string $status,
        #[OA\Property(description: 'Owner user UUID', format: 'uuid')]
        public string $ownerUserId,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Last update timestamp', format: 'date-time', nullable: true)]
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
