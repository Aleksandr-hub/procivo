<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListTasks;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListTasksQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $assigneeId = null,
        public ?string $candidateEmployeeId = null,
    ) {
    }
}
