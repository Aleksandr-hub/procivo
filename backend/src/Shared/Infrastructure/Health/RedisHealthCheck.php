<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

final class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    public function check(): array
    {
        try {
            $pong = $this->redis->ping();

            if ($pong === true || $pong === '+PONG') {
                return [
                    'name' => 'redis',
                    'healthy' => true,
                    'message' => 'Redis connection OK',
                ];
            }

            return [
                'name' => 'redis',
                'healthy' => false,
                'message' => 'Redis ping returned unexpected response',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'redis',
                'healthy' => false,
                'message' => 'Redis connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
