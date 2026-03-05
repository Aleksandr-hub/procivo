<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Comment;

final readonly class CommentDTO
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $authorId,
        public ?string $parentId,
        public string $body,
        public string $createdAt,
        public ?string $updatedAt,
        public ?string $authorName = null,
        public ?string $authorAvatarUrl = null,
    ) {
    }

    public static function fromEntity(Comment $comment, ?string $authorName = null, ?string $authorAvatarUrl = null): self
    {
        return new self(
            id: $comment->id()->value(),
            taskId: $comment->taskId()->value(),
            authorId: $comment->authorId(),
            parentId: $comment->parentId()?->value(),
            body: $comment->body(),
            createdAt: $comment->createdAt()->value()->format(\DateTimeInterface::ATOM),
            updatedAt: $comment->updatedAt()?->format(\DateTimeInterface::ATOM),
            authorName: $authorName,
            authorAvatarUrl: $authorAvatarUrl,
        );
    }
}
