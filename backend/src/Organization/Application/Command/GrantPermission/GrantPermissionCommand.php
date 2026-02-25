<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\GrantPermission;

use App\Shared\Application\Command\CommandInterface;

final readonly class GrantPermissionCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $roleId,
        public string $organizationId,
        public string $resource,
        public string $action,
        public string $scope,
    ) {
    }
}
