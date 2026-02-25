<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetDepartmentTree;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetDepartmentTreeQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
