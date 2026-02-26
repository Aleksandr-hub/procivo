<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Repository;

use App\TaskManager\Domain\Entity\Comment;
use App\TaskManager\Domain\ValueObject\CommentId;
use App\TaskManager\Domain\ValueObject\TaskId;

interface CommentRepositoryInterface
{
    public function save(Comment $comment): void;

    public function remove(Comment $comment): void;

    public function findById(CommentId $id): ?Comment;

    /**
     * @return list<Comment>
     */
    public function findByTaskId(TaskId $taskId): array;
}
