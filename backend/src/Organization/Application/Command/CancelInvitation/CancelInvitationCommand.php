<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CancelInvitation;

use App\Shared\Application\Command\CommandInterface;

final readonly class CancelInvitationCommand implements CommandInterface
{
    public function __construct(
        public string $invitationId,
    ) {
    }
}
