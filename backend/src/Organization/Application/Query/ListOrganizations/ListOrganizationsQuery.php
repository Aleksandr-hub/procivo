<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListOrganizations;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListOrganizationsQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
    ) {
    }
}
