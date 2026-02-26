<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\RemoveAssignment;

use App\Shared\Application\Command\CommandInterface;

final readonly class RemoveAssignmentCommand implements CommandInterface
{
    public function __construct(
        public string $assignmentId,
    ) {
    }
}
