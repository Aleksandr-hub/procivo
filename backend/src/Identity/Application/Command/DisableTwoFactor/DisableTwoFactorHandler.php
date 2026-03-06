<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DisableTwoFactor;

use App\Identity\Application\Port\TotpServiceInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DisableTwoFactorHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TotpServiceInterface $totpService,
    ) {
    }

    public function __invoke(DisableTwoFactorCommand $command): void
    {
        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $command->userId));
        }

        if (!$user->isTotpEnabled()) {
            throw new DomainException('Two-factor authentication is not enabled.');
        }

        $encryptedSecret = $user->totpSecret();

        if (null === $encryptedSecret) {
            throw new DomainException('No TOTP secret found.');
        }

        $plainSecret = $this->totpService->decryptSecret($encryptedSecret);

        if (!$this->totpService->verifyCode($plainSecret, $command->code)) {
            throw new DomainException('Invalid TOTP code.');
        }

        $user->disableTotp();

        $this->userRepository->save($user);
    }
}
