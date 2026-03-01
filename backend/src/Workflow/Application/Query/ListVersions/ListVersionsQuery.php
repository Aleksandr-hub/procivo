<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\ListVersions;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListVersionsQuery implements QueryInterface
{
    public function __construct(
        public string $processDefinitionId,
    ) {
    }
}
