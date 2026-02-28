<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\BatchTaskWorkflowSummary;

use App\Shared\Application\Query\QueryInterface;

final readonly class BatchTaskWorkflowSummaryQuery implements QueryInterface
{
    /**
     * @param list<string> $taskIds
     */
    public function __construct(
        public array $taskIds,
    ) {
    }
}
