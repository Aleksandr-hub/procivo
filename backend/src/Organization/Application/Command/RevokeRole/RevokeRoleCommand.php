<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RevokeRole;

use App\Shared\Application\Command\CommandInterface;

final readonly class RevokeRoleCommand implements CommandInterface
{
    public function __construct(
        public string $employeeId,
        public string $roleId,
    ) {
    }
}
