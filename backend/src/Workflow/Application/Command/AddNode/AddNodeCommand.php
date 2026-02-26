<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\AddNode;

use App\Shared\Application\Command\CommandInterface;

final readonly class AddNodeCommand implements CommandInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        public string $id,
        public string $processDefinitionId,
        public string $type,
        public string $name,
        public ?string $description = null,
        public array $config = [],
        public float $positionX = 0.0,
        public float $positionY = 0.0,
    ) {
    }
}
