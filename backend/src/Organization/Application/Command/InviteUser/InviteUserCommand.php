<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\InviteUser;

use App\Shared\Application\Command\CommandInterface;

final readonly class InviteUserCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $email,
        public string $departmentId,
        public string $positionId,
        public string $employeeNumber,
        public string $invitedByUserId,
    ) {
    }
}
