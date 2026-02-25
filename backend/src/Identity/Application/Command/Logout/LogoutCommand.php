<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\Logout;

use App\Shared\Application\Command\CommandInterface;

final readonly class LogoutCommand implements CommandInterface
{
    public function __construct(
        public string $refreshToken,
    ) {
    }
}
