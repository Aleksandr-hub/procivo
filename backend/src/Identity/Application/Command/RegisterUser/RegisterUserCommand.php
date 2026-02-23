<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RegisterUser;

use App\Shared\Application\Command\CommandInterface;

final readonly class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
    ) {
    }
}
