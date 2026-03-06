<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ConfirmTwoFactor;

use App\Shared\Application\Command\CommandInterface;

final readonly class ConfirmTwoFactorCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $code,
    ) {
    }
}
