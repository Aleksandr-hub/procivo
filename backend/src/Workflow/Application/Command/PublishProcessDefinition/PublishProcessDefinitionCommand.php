<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\PublishProcessDefinition;

use App\Shared\Application\Command\CommandInterface;

final readonly class PublishProcessDefinitionCommand implements CommandInterface
{
    public function __construct(
        public string $processDefinitionId,
        public string $publishedBy,
    ) {
    }
}
