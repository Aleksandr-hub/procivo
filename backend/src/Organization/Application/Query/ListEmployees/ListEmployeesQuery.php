<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployees;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListEmployeesQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $departmentId = null,
    ) {
    }
}
