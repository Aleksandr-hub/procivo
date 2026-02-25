<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SuspendOrganization;

use App\Shared\Application\Command\CommandInterface;

final readonly class SuspendOrganizationCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
