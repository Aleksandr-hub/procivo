<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\Logout;

use App\Identity\Application\Port\RefreshTokenManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class LogoutHandler
{
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
    ) {
    }

    public function __invoke(LogoutCommand $command): void
    {
        if ('' !== $command->refreshToken) {
            $this->refreshTokenManager->revoke($command->refreshToken);
        }
    }
}
