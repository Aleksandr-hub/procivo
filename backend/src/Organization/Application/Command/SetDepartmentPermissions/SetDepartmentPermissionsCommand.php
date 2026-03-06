<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SetDepartmentPermissions;

use App\Shared\Application\Command\CommandInterface;

final readonly class SetDepartmentPermissionsCommand implements CommandInterface
{
    /**
     * @param list<array{resource: string, action: string, scope: string}> $permissions
     */
    public function __construct(
        public string $departmentId,
        public string $organizationId,
        public array $permissions,
        public string $actorId,
    ) {
    }
}
