<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskStatus;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;

    public function remove(Task $task): void;

    public function findById(TaskId $id): ?Task;

    /**
     * @return list<Task>
     */
    public function findByOrganizationId(string $organizationId, ?TaskStatus $status = null, ?string $assigneeId = null): array;

    /**
     * Find task by ID with pessimistic write lock (SELECT ... FOR UPDATE).
     * Must be called inside an active transaction.
     */
    public function findByIdForUpdate(TaskId $id): ?Task;

    /**
     * @param list<string> $roleIds
     *
     * @return list<Task>
     */
    public function findAvailableForEmployee(string $organizationId, array $roleIds, ?string $departmentId): array;
}
