<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Role;

final readonly class RoleDTO
{
    /**
     * @param list<PermissionDTO> $permissions
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public bool $isSystem,
        public int $hierarchy,
        public string $createdAt,
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
