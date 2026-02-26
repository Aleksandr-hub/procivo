<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AddAssignment;

use App\Shared\Application\Command\CommandInterface;

final readonly class AddAssignmentCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $employeeId,
        public string $role,
        public string $assignedBy,
    ) {
    }
}
