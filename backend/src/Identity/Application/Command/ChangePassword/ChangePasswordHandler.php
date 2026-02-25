<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangePassword;

use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\Exception\InvalidCredentialsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ChangePasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $command->userId));
        }

        if (!$this->passwordHasher->verify($user->password()->value(), $command->currentPassword)) {
            throw InvalidCredentialsException::wrongCurrentPassword();
        }

        $user->changePassword(
            new HashedPassword($this->passwordHasher->hash($command->newPassword)),
        );

        $this->userRepository->save($user);
    }
}
