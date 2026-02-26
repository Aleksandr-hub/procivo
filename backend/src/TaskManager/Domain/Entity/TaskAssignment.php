<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\TaskManager\Domain\ValueObject\AssignmentRole;
use App\TaskManager\Domain\ValueObject\TaskId;

class TaskAssignment
{
    private string $id;
    private string $taskId;
    private string $employeeId;
    private string $role;
    private string $assignedBy;
    private \DateTimeImmutable $assignedAt;

    private function __construct()
    {
    }

    public static function create(
        string $id,
        TaskId $taskId,
        string $employeeId,
        AssignmentRole $role,
        string $assignedBy,
    ): self {
        $assignment = new self();
        $assignment->id = $id;
        $assignment->taskId = $taskId->value();
        $assignment->employeeId = $employeeId;
        $assignment->role = $role->value;
        $assignment->assignedBy = $assignedBy;
        $assignment->assignedAt = new \DateTimeImmutable();

        return $assignment;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function employeeId(): string
    {
        return $this->employeeId;
    }

    public function role(): AssignmentRole
    {
        return AssignmentRole::from($this->role);
    }

    public function assignedBy(): string
    {
        return $this->assignedBy;
    }

    public function assignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
