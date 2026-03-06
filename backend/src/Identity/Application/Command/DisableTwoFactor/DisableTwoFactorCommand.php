<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DisableTwoFactor;

use App\Shared\Application\Command\CommandInterface;

final readonly class DisableTwoFactorCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $code,
    ) {
    }
}
