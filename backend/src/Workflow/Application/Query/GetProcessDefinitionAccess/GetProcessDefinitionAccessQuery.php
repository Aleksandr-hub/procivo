<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessDefinitionAccess;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetProcessDefinitionAccessQuery implements QueryInterface
{
    public function __construct(
        public string $processDefinitionId,
    ) {
    }
}
