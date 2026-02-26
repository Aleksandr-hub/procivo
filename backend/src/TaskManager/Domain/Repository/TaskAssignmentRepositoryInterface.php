<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\TaskManager\Domain\Entity\TaskAssignment;
use App\TaskManager\Domain\ValueObject\TaskId;

interface TaskAssignmentRepositoryInterface
{
    public function save(TaskAssignment $assignment): void;

    public function remove(TaskAssignment $assignment): void;

    public function findById(string $id): ?TaskAssignment;

    /**
     * @return list<TaskAssignment>
     */
    public function findByTaskId(TaskId $taskId): array;

    public function findByTaskAndEmployee(TaskId $taskId, string $employeeId, string $role): ?TaskAssignment;

    public function removeAllByTaskId(TaskId $taskId): void;
}
