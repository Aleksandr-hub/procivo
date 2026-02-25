<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateOrganization;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
    ) {
    }
}
