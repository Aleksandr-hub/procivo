<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateProcessBoard;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateProcessBoardCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public string $processDefinitionId,
    ) {
    }
}
