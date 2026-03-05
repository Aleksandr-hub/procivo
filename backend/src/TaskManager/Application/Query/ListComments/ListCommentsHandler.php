<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListComments;

use App\Identity\Application\Port\AvatarStorageInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\TaskManager\Application\DTO\CommentDTO;
use App\TaskManager\Domain\Repository\CommentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListCommentsHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private UserRepositoryInterface $userRepository,
        private AvatarStorageInterface $avatarStorage,
    ) {
    }

    /**
     * @return list<CommentDTO>
     */
    public function __invoke(ListCommentsQuery $query): array
    {
        $comments = $this->commentRepository->findByTaskId(
            TaskId::fromString($query->taskId),
        );

        $authorIds = array_unique(array_map(static fn ($c) => $c->authorId(), $comments));
        $authorMap = [];
        foreach ($authorIds as $authorId) {
            $user = $this->userRepository->findById(UserId::fromString($authorId));
            if (null !== $user) {
                $fullName = trim($user->firstName().' '.$user->lastName());
                $avatarUrl = null;
                if (null !== $user->avatarPath()) {
                    $avatarUrl = $this->avatarStorage->getUrl($user->avatarPath());
                }
                $authorMap[$authorId] = [
                    'name' => '' !== $fullName ? $fullName : $user->email()->value(),
                    'avatarUrl' => $avatarUrl,
                ];
            }
        }

        return array_map(
            static fn ($c) => CommentDTO::fromEntity(
                $c,
                $authorMap[$c->authorId()]['name'] ?? null,
                $authorMap[$c->authorId()]['avatarUrl'] ?? null,
            ),
            $comments,
        );
    }
}
