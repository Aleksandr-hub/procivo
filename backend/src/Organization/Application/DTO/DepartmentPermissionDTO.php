<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\DepartmentPermission;

final readonly class DepartmentPermissionDTO
{
    public function __construct(
        public string $id,
        public string $departmentId,
        public string $resource,
        public string $action,
        public string $scope,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(DepartmentPermission $permission): self
    {
        return new self(
            id: $permission->id()->value(),
            departmentId: $permission->departmentId()->value(),
            resource: $permission->resource()->value,
            action: $permission->action()->value,
            scope: $permission->scope()->value,
            createdAt: $permission->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
