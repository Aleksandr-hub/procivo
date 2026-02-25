<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RevokePermission;

use App\Shared\Application\Command\CommandInterface;

final readonly class RevokePermissionCommand implements CommandInterface
{
    public function __construct(
        public string $permissionId,
    ) {
    }
}
