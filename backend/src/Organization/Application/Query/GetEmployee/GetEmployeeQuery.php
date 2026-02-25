<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEmployee;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetEmployeeQuery implements QueryInterface
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
