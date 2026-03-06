<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

final readonly class EffectivePermissionDTO
{
    public function __construct(
        public string $resource,
        public string $action,
        public string $scope,
    ) {
    }
}
