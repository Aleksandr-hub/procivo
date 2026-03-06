<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Doctrine\DBAL\Connection;

final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function check(): array
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return [
                'name' => 'database',
                'healthy' => true,
                'message' => 'PostgreSQL connection OK',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'database',
                'healthy' => false,
                'message' => 'PostgreSQL connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
