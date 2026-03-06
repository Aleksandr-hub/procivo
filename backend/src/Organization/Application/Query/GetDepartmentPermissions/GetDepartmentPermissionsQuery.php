<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetDepartmentPermissions;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetDepartmentPermissionsQuery implements QueryInterface
{
    public function __construct(
        public string $departmentId,
    ) {
    }
}
