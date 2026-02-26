<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\UpdateNode;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateNodeCommand implements CommandInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        public string $nodeId,
        public string $name,
        public ?string $description,
        public array $config,
        public float $positionX,
        public float $positionY,
    ) {
    }
}
