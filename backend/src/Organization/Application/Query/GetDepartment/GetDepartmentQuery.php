<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetDepartment;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetDepartmentQuery implements QueryInterface
{
    public function __construct(
        public string $departmentId,
    ) {
    }
}
