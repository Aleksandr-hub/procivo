<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTask;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetTaskQuery implements QueryInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
