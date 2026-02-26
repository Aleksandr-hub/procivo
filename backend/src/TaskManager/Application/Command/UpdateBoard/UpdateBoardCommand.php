<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateBoard;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateBoardCommand implements CommandInterface
{
    public function __construct(
        public string $boardId,
        public string $name,
        public ?string $description = null,
    ) {
    }
}
