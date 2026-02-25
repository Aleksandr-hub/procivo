<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetSubordinates;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetSubordinatesQuery implements QueryInterface
{
    public function __construct(
        public string $employeeId,
        public bool $recursive = false,
    ) {
    }
}
