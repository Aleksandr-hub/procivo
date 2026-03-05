<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\PromoteUser;

use App\Shared\Application\Command\CommandInterface;

final readonly class PromoteUserCommand implements CommandInterface
{
    public function __construct(
        public string $email,
        public string $role,
    ) {
    }
}
