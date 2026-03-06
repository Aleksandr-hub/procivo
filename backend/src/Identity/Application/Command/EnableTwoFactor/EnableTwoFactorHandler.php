<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\EnableTwoFactor;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class EnableTwoFactorHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(EnableTwoFactorCommand $command): void
    {
        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $command->userId));
        }

        // Store encrypted secret and backup codes on user (but do NOT enable yet).
        // The user must confirm with a valid TOTP code via ConfirmTwoFactorCommand.
        $user->setTotpSecret($command->encryptedSecret);
        $user->setBackupCodes($command->hashedBackupCodes);

        $this->userRepository->save($user);
    }
}
