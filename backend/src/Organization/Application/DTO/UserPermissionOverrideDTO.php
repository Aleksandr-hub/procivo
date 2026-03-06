<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\UserPermissionOverride;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'User-level permission override (allow or deny)')]
final readonly class UserPermissionOverrideDTO
{
    public function __construct(
        #[OA\Property(description: 'Override UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Employee UUID', format: 'uuid')]
        public string $employeeId,
        #[OA\Property(description: 'Resource type', example: 'task')]
        public string $resource,
        #[OA\Property(description: 'Action type', example: 'delete')]
        public string $action,
        #[OA\Property(description: 'Override effect', enum: ['allow', 'deny'])]
        public string $effect,
        #[OA\Property(description: 'Permission scope', enum: ['own', 'department', 'organization'])]
        public string $scope,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
    ) {
    }

    public static function fromEntity(UserPermissionOverride $override): self
    {
        return new self(
            id: $override->id()->value(),
            employeeId: $override->employeeId()->value(),
            resource: $override->resource()->value,
            action: $override->action()->value,
            effect: $override->effect()->value,
            scope: $override->scope()->value,
            createdAt: $override->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
