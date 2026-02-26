<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AddComment;

use App\TaskManager\Domain\Entity\Comment;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\CommentRepositoryInterface;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\CommentId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AddCommentHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function __invoke(AddCommentCommand $command): void
    {
        $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        $comment = Comment::create(
            id: CommentId::fromString($command->id),
            taskId: TaskId::fromString($command->taskId),
            authorId: $command->authorId,
            body: $command->body,
            parentId: null !== $command->parentId ? CommentId::fromString($command->parentId) : null,
        );

        $this->commentRepository->save($comment);
    }
}
