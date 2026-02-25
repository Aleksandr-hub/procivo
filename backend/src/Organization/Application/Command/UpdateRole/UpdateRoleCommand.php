<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateRole;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateRoleCommand implements CommandInterface
{
    public function __construct(
        public string $roleId,
        public string $name,
        public ?string $description,
        public int $hierarchy,
    ) {
    }
}
