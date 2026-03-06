<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetUserOverrides;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetUserOverridesQuery implements QueryInterface
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
