<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AssignTask;

use App\Shared\Application\Command\CommandInterface;

final readonly class AssignTaskCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
        public ?string $assigneeId,
        public string $actorId,
    ) {
    }
}
