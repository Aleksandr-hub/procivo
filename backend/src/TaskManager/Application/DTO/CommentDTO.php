<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Comment;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Task comment with optional threading')]
final readonly class CommentDTO
{
    public function __construct(
        #[OA\Property(description: 'Comment UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Task UUID', format: 'uuid')]
        public string $taskId,
        #[OA\Property(description: 'Author user UUID', format: 'uuid')]
        public string $authorId,
        #[OA\Property(description: 'Parent comment UUID (for replies)', format: 'uuid', nullable: true)]
        public ?string $parentId,
        #[OA\Property(description: 'Comment body text')]
        public string $body,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Last update timestamp', format: 'date-time', nullable: true)]
        public ?string $updatedAt,
        #[OA\Property(description: 'Author display name', nullable: true)]
        public ?string $authorName = null,
        #[OA\Property(description: 'Author avatar URL', format: 'uri', nullable: true)]
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
