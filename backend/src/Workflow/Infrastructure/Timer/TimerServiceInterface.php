<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Timer;

interface TimerServiceInterface
{
    public function scheduleTimer(string $processInstanceId, string $tokenId, string $nodeId, \DateTimeImmutable $fireAt): void;
}
