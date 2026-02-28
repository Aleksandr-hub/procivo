<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateTask;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateTaskCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $title,
        public ?string $description,
        public string $priority,
        public ?string $dueDate,
        public ?float $estimatedHours,
        public string $creatorId,
        public ?string $assigneeId = null,
        public string $assignmentStrategy = 'unassigned',
        public ?string $assigneeEmployeeId = null,
        public ?string $assigneeRoleId = null,
        public ?string $assigneeDepartmentId = null,
        /** @var array<string, mixed>|null */
        public ?array $formSchema = null,
    ) {
    }
}
