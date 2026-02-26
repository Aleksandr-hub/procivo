<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListComments;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListCommentsQuery implements QueryInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
