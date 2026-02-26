<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\RemoveNode;

use App\Shared\Application\Command\CommandInterface;

final readonly class RemoveNodeCommand implements CommandInterface
{
    public function __construct(
        public string $nodeId,
    ) {
    }
}
