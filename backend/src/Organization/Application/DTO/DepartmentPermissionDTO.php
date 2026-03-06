<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\DepartmentPermission;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Permission assigned to a department')]
final readonly class DepartmentPermissionDTO
{
    public function __construct(
        #[OA\Property(description: 'Permission UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Department UUID', format: 'uuid')]
        public string $departmentId,
        #[OA\Property(description: 'Resource type', example: 'task')]
        public string $resource,
        #[OA\Property(description: 'Action type', example: 'view')]
        public string $action,
        #[OA\Property(description: 'Permission scope', enum: ['own', 'department', 'organization'])]
        public string $scope,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
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
