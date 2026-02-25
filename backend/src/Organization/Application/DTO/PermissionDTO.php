<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Permission;

final readonly class PermissionDTO
{
    public function __construct(
        public string $id,
        public string $roleId,
        public string $resource,
        public string $action,
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
