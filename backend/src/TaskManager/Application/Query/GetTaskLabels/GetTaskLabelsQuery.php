<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTaskLabels;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetTaskLabelsQuery implements QueryInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
