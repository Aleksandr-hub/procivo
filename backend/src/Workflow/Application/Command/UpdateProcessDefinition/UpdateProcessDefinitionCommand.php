<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\UpdateProcessDefinition;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateProcessDefinitionCommand implements CommandInterface
{
    public function __construct(
        public string $processDefinitionId,
        public string $name,
        public ?string $description,
    ) {
    }
}
