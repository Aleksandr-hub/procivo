<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListPositions;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListPositionsQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $departmentId = null,
    ) {
    }
}
