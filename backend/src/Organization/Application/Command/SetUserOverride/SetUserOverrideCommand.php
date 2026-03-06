<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SetUserOverride;

use App\Shared\Application\Command\CommandInterface;

final readonly class SetUserOverrideCommand implements CommandInterface
{
    public function __construct(
        public string $employeeId,
        public string $organizationId,
        public string $resource,
        public string $action,
        public string $effect,
        public string $scope,
        public string $actorId,
    ) {
    }
}
