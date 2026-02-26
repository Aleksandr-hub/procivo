<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteTask;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteTaskCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
