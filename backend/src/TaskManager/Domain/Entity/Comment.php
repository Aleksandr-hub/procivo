<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\TaskManager\Domain\Event\CommentAddedEvent;
use App\TaskManager\Domain\ValueObject\CommentId;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;

class Comment extends AggregateRoot
{
    private string $id;
    private string $taskId;
    private string $authorId;
    private ?string $parentId;
    private string $body;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        CommentId $id,
        TaskId $taskId,
        string $authorId,
        string $body,
        ?CommentId $parentId = null,
    ): self {
        $comment = new self();
        $comment->id = $id->value();
        $comment->taskId = $taskId->value();
        $comment->authorId = $authorId;
        $comment->parentId = $parentId?->value();
        $comment->body = $body;
        $comment->createdAt = new \DateTimeImmutable();
        $comment->updatedAt = null;

        $comment->recordEvent(new CommentAddedEvent(
            $id->value(),
            $taskId->value(),
            $authorId,
        ));

        return $comment;
    }

    public function updateBody(string $body): void
    {
        $this->body = $body;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): CommentId
    {
        return CommentId::fromString($this->id);
    }

    public function taskId(): TaskId
    {
        return TaskId::fromString($this->taskId);
    }

    public function authorId(): string
    {
        return $this->authorId;
    }

    public function parentId(): ?CommentId
    {
        return null !== $this->parentId ? CommentId::fromString($this->parentId) : null;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function createdAt(): CreatedAt
    {
        return new CreatedAt($this->createdAt);
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
