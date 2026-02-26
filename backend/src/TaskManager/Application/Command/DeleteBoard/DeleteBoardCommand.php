<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteBoard;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteBoardCommand implements CommandInterface
{
    public function __construct(
        public string $boardId,
    ) {
    }
}
