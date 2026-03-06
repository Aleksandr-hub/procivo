<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\EnableTwoFactor;

use App\Shared\Application\Command\CommandInterface;

final readonly class EnableTwoFactorCommand implements CommandInterface
{
    /**
     * @param list<string> $hashedBackupCodes
     */
    public function __construct(
        public string $userId,
        public string $encryptedSecret,
        public array $hashedBackupCodes,
    ) {
    }
}
