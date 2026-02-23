<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class SecurityUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail(new Email($identifier));

        if (null === $user) {
            throw new UserNotFoundException(\sprintf('User "%s" not found.', $identifier));
        }

        return new SecurityUser(
            id: $user->id()->value(),
            email: $user->email()->value(),
            password: $user->password()->value(),
            roles: $user->roles(),
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }
}
