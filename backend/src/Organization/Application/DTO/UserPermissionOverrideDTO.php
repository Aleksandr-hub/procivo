<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\UserPermissionOverride;

final readonly class UserPermissionOverrideDTO
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $resource,
        public string $action,
        public string $effect,
        public string $scope,
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
