<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UnclaimTask;

use App\Shared\Application\Command\CommandInterface;

final readonly class UnclaimTaskCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
        public string $employeeId,
        public string $actorId,
    ) {
    }
}
