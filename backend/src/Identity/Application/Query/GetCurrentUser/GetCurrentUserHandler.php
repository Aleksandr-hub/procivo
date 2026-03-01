<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\GetCurrentUser;

use App\Identity\Application\DTO\UserDTO;
use App\Identity\Application\Port\AvatarStorageInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetCurrentUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AvatarStorageInterface $avatarStorage,
    ) {
    }

    public function __invoke(GetCurrentUserQuery $query): UserDTO
    {
        $user = $this->userRepository->findById(UserId::fromString($query->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $query->userId));
        }

        $avatarUrl = null !== $user->avatarPath()
            ? $this->avatarStorage->getUrl($user->avatarPath())
            : null;

        return UserDTO::fromEntity($user, $avatarUrl);
    }
}
