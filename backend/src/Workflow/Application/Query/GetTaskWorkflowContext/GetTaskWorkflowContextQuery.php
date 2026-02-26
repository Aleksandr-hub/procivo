<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetTaskWorkflowContext;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetTaskWorkflowContextQuery implements QueryInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
