<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetDashboardStats;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetDashboardStatsQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
