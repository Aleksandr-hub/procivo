<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\TaskManager\Domain\Entity\TaskAttachment;
use App\TaskManager\Domain\ValueObject\AttachmentId;
use App\TaskManager\Domain\ValueObject\TaskId;

interface TaskAttachmentRepositoryInterface
{
    public function save(TaskAttachment $attachment): void;

    public function remove(TaskAttachment $attachment): void;

    public function findById(AttachmentId $id): ?TaskAttachment;

    /**
     * @return list<TaskAttachment>
     */
    public function findByTaskId(TaskId $taskId): array;
}
