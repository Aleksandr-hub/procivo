<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTaskAssignments;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetTaskAssignmentsQuery implements QueryInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
