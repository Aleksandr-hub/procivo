<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\EndImpersonation;

use App\Shared\Application\Command\CommandInterface;

final readonly class EndImpersonationCommand implements CommandInterface
{
    public function __construct(
        public string $adminUserId,
    ) {
    }
}
