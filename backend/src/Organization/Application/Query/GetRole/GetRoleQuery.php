<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetRole;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetRoleQuery implements QueryInterface
{
    public function __construct(
        public string $roleId,
    ) {
    }
}
