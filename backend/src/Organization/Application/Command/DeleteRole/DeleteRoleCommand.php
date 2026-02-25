<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeleteRole;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteRoleCommand implements CommandInterface
{
    public function __construct(
        public string $roleId,
    ) {
    }
}
