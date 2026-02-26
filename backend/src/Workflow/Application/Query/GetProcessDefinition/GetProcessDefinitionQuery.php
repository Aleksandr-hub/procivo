<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessDefinition;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetProcessDefinitionQuery implements QueryInterface
{
    public function __construct(
        public string $processDefinitionId,
    ) {
    }
}
