<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RemoveUserOverride;

use App\Shared\Application\Command\CommandInterface;

final readonly class RemoveUserOverrideCommand implements CommandInterface
{
    public function __construct(
        public string $overrideId,
        public string $organizationId,
        public string $actorId,
    ) {
    }
}
