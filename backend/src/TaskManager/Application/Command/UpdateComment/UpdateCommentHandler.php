<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateComment;

use App\TaskManager\Domain\Exception\CommentNotFoundException;
use App\TaskManager\Domain\Repository\CommentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\CommentId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateCommentHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function __invoke(UpdateCommentCommand $command): void
    {
        $comment = $this->commentRepository->findById(CommentId::fromString($command->commentId));

        if (null === $comment) {
            throw CommentNotFoundException::withId($command->commentId);
        }

        $comment->updateBody($command->body);
        $this->commentRepository->save($comment);
    }
}
