<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEffectivePermissions;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetEffectivePermissionsQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
        public string $organizationId,
    ) {
    }
}
