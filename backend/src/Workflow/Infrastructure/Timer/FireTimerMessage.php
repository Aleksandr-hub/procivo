<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Timer;

final readonly class FireTimerMessage
{
    public function __construct(
        public string $processInstanceId,
        public string $tokenId,
        public string $nodeId,
    ) {
    }
}
