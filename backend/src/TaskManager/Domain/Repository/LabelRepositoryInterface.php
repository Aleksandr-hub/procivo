<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\TaskManager\Domain\Entity\Label;
use App\TaskManager\Domain\ValueObject\LabelId;
use App\TaskManager\Domain\ValueObject\TaskId;

interface LabelRepositoryInterface
{
    public function save(Label $label): void;

    public function remove(Label $label): void;

    public function findById(LabelId $id): ?Label;

    /**
     * @return list<Label>
     */
    public function findByOrganizationId(string $organizationId): array;

    public function assignToTask(LabelId $labelId, TaskId $taskId): void;

    public function removeFromTask(LabelId $labelId, TaskId $taskId): void;

    /**
     * @return list<Label>
     */
    public function findByTaskId(TaskId $taskId): array;

    /**
     * @return array<string, list<string>> Map of taskId => list of labelIds
     */
    public function findLabelIdsByTaskIds(string ...$taskIds): array;
}
