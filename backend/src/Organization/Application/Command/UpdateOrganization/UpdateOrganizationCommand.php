<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateOrganization;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public ?string $description,
    ) {
    }
}
