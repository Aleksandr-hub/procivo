<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Permission;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Role permission entry')]
final readonly class PermissionDTO
{
    public function __construct(
        #[OA\Property(description: 'Permission UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Role UUID', format: 'uuid')]
        public string $roleId,
        #[OA\Property(description: 'Resource type', example: 'task')]
        public string $resource,
        #[OA\Property(description: 'Action type', example: 'view')]
        public string $action,
        #[OA\Property(description: 'Permission scope', enum: ['own', 'department', 'organization'])]
        public string $scope,
    ) {
    }

    public static function fromEntity(Permission $permission): self
    {
        return new self(
            id: $permission->id()->value(),
            roleId: $permission->roleId()->value(),
            resource: $permission->resource()->value,
            action: $permission->action()->value,
            scope: $permission->scope()->value,
        );
    }
}
