<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\DeleteProcessDefinition;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteProcessDefinitionCommand implements CommandInterface
{
    public function __construct(
        public string $processDefinitionId,
    ) {
    }
}
