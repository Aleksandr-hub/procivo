<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetMyPermissions;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetMyPermissionsQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
        public string $organizationId,
    ) {
    }
}
