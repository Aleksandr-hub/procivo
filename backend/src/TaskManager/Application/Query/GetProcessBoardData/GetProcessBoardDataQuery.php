<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetProcessBoardData;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetProcessBoardDataQuery implements QueryInterface
{
    public function __construct(
        public string $boardId,
        public string $organizationId,
    ) {
    }
}
