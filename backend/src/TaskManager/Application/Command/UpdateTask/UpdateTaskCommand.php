<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateTask;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateTaskCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
        public string $title,
        public ?string $description,
        public string $priority,
        public ?string $dueDate,
        public ?float $estimatedHours,
    ) {
    }
}
