<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangePassword;

use App\Shared\Application\Command\CommandInterface;

final readonly class ChangePasswordCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}
