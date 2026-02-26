<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListLabels;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListLabelsQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
