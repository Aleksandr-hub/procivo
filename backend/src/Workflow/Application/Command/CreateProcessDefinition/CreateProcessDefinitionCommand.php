<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\CreateProcessDefinition;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateProcessDefinitionCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public string $createdBy,
    ) {
    }
}
