<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Role;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Organization role with associated permissions')]
final readonly class RoleDTO
{
    /**
     * @param list<PermissionDTO> $permissions
     */
    public function __construct(
        #[OA\Property(description: 'Role UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Role name')]
        public string $name,
        #[OA\Property(description: 'Role description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Whether this is a system-defined role')]
        public bool $isSystem,
        #[OA\Property(description: 'Role hierarchy level (lower = higher authority)')]
        public int $hierarchy,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Associated permissions', type: 'array', items: new OA\Items(ref: new \Nelmio\ApiDocBundle\Attribute\Model(type: PermissionDTO::class)))]
        public array $permissions = [],
    ) {
    }

    /**
     * @param list<PermissionDTO> $permissions
     */
    public static function fromEntity(Role $role, array $permissions = []): self
    {
        return new self(
            id: $role->id()->value(),
            organizationId: $role->organizationId()->value(),
            name: $role->name(),
            description: $role->description(),
            isSystem: $role->isSystem(),
            hierarchy: $role->hierarchy(),
            createdAt: $role->createdAt()->value()->format(\DateTimeInterface::ATOM),
            permissions: $permissions,
        );
    }
}
