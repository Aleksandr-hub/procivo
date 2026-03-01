<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\TransitionTask;

use App\Shared\Application\Command\CommandInterface;

final readonly class TransitionTaskCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
        public string $transition,
        public string $actorId,
    ) {
    }
}
