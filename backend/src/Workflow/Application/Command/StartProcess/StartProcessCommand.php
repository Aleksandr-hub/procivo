<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\StartProcess;

use App\Shared\Application\Command\CommandInterface;

final readonly class StartProcessCommand implements CommandInterface
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $id,
        public string $processDefinitionId,
        public string $organizationId,
        public string $startedBy,
        public array $variables = [],
    ) {
    }
}
