<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\MigrateProcessInstances;

use App\Shared\Application\Command\CommandInterface;

final readonly class MigrateProcessInstancesCommand implements CommandInterface
{
    public function __construct(
        public string $processDefinitionId,
        public string $targetVersionId,
        public string $migratedBy,
    ) {
    }
}
