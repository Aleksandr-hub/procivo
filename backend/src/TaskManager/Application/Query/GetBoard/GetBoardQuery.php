<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetBoard;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetBoardQuery implements QueryInterface
{
    public function __construct(
        public string $boardId,
    ) {
    }
}
