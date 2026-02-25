<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetOrgChart;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetOrgChartQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
