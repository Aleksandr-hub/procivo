<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\UpdateProfile;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateProfileCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {
    }
}
