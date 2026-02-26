<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\RevertProcessDefinitionToDraft;

use App\Shared\Application\Command\CommandInterface;

final readonly class RevertProcessDefinitionToDraftCommand implements CommandInterface
{
    public function __construct(
        public string $processDefinitionId,
        public string $revertedBy,
    ) {
    }
}
