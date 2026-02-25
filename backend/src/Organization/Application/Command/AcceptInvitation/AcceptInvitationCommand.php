<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AcceptInvitation;

use App\Shared\Application\Command\CommandInterface;

final readonly class AcceptInvitationCommand implements CommandInterface
{
    public function __construct(
        public string $token,
        public string $firstName,
        public string $lastName,
        public string $password,
    ) {
    }
}
