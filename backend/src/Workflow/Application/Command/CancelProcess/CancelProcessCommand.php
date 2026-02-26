<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\CancelProcess;

use App\Shared\Application\Command\CommandInterface;

final readonly class CancelProcessCommand implements CommandInterface
{
    public function __construct(
        public string $processInstanceId,
        public string $cancelledBy,
        public ?string $reason = null,
    ) {
    }
}
