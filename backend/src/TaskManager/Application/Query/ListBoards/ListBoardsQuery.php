<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListBoards;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListBoardsQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
