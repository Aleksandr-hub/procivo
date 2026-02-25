<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEmployeeRoles;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetEmployeeRolesQuery implements QueryInterface
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
