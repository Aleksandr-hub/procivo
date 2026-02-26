<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessInstance;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetProcessInstanceQuery implements QueryInterface
{
    public function __construct(
        public string $processInstanceId,
    ) {
    }
}
