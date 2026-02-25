<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SeedDefaultRoles;

use App\Shared\Application\Command\CommandInterface;

final readonly class SeedDefaultRolesCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
