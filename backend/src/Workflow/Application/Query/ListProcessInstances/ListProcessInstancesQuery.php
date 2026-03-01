<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\ListProcessInstances;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListProcessInstancesQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $search = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
