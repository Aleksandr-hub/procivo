<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Identity;

use App\Identity\Application\Port\AvatarStorageInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\TaskManager\Application\Port\UserQueryPort;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final readonly class DoctrineUserQueryAdapter implements UserQueryPort
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AvatarStorageInterface $avatarStorage,
    ) {
    }

    /**
     * @param list<string> $userIds
     *
     * @return array<string, string> Map of userId => displayName
     */
    public function resolveDisplayNames(array $userIds): array
    {
        $map = [];

        foreach ($userIds as $userId) {
            if ('system' === $userId || '' === $userId || !SymfonyUuid::isValid($userId)) {
                continue;
            }

            $user = $this->userRepository->findById(UserId::fromString($userId));
            if (null === $user) {
                continue;
            }

            $fullName = trim($user->firstName().' '.$user->lastName());
            $map[$userId] = '' !== $fullName ? $fullName : $user->email()->value();
        }

        return $map;
    }

    /**
     * @param list<string> $userIds
     *
     * @return array<string, array{name: string, avatarUrl: string|null}> Map of userId => {name, avatarUrl}
     */
    public function resolveDisplayNamesWithAvatars(array $userIds): array
    {
        $map = [];

        foreach ($userIds as $userId) {
            if ('system' === $userId || '' === $userId || !SymfonyUuid::isValid($userId)) {
                continue;
            }

            $user = $this->userRepository->findById(UserId::fromString($userId));
            if (null === $user) {
                continue;
            }

            $fullName = trim($user->firstName().' '.$user->lastName());
            $avatarUrl = null;
            if (null !== $user->avatarPath()) {
                $avatarUrl = $this->avatarStorage->getUrl($user->avatarPath());
            }

            $map[$userId] = [
                'name' => '' !== $fullName ? $fullName : $user->email()->value(),
                'avatarUrl' => $avatarUrl,
            ];
        }

        return $map;
    }
}
