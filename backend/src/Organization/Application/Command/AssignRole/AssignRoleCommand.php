<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AssignRole;

use App\Shared\Application\Command\CommandInterface;

final readonly class AssignRoleCommand implements CommandInterface
{
    public function __construct(
        public string $employeeId,
        public string $roleId,
        public string $organizationId,
    ) {
    }
}
