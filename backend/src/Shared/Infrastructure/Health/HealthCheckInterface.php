<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

interface HealthCheckInterface
{
    /**
     * @return array{name: string, healthy: bool, message: string}
     */
    public function check(): array;
}
