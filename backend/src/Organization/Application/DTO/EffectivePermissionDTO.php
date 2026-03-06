<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Effective permission computed from role, department, and user overrides')]
final readonly class EffectivePermissionDTO
{
    public function __construct(
        #[OA\Property(description: 'Resource type', example: 'task')]
        public string $resource,
        #[OA\Property(description: 'Action type', example: 'view')]
        public string $action,
        #[OA\Property(description: 'Permission scope', enum: ['own', 'department', 'organization'])]
        public string $scope,
    ) {
    }
}
