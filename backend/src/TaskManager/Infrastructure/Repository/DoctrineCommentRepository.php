<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\TaskManager\Domain\Entity\Comment;
use App\TaskManager\Domain\Repository\CommentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\CommentId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineCommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Comment $comment): void
    {
        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }

    public function remove(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }

    public function findById(CommentId $id): ?Comment
    {
        return $this->entityManager->find(Comment::class, $id->value());
    }

    /**
     * @return list<Comment>
     */
    public function findByTaskId(TaskId $taskId): array
    {
        return $this->entityManager->getRepository(Comment::class)->findBy(
            ['taskId' => $taskId->value()],
            ['createdAt' => 'ASC'],
        );
    }
}
