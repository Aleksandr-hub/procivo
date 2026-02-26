<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteColumn;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteColumnCommand implements CommandInterface
{
    public function __construct(
        public string $columnId,
    ) {
    }
}
