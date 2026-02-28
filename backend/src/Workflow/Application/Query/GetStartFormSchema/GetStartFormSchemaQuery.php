<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetStartFormSchema;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetStartFormSchemaQuery implements QueryInterface
{
    public function __construct(
        public string $processDefinitionId,
    ) {
    }
}
