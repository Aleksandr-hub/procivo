<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\CompleteTaskNode;

use App\Shared\Application\Command\CommandInterface;

final readonly class CompleteTaskNodeCommand implements CommandInterface
{
    public function __construct(
        public string $processInstanceId,
        public string $tokenId,
    ) {
    }
}
