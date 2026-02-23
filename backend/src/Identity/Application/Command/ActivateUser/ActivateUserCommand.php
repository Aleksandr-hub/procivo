<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ActivateUser;

use App\Shared\Application\Command\CommandInterface;

final readonly class ActivateUserCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
    ) {
    }
}
