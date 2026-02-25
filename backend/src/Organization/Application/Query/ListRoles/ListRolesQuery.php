<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListRoles;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListRolesQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
