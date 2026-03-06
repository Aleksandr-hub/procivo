<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

final class RabbitMqHealthCheck implements HealthCheckInterface
{
    private const TIMEOUT_SECONDS = 3;

    public function check(): array
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket === false) {
            return [
                'name' => 'rabbitmq',
                'healthy' => false,
                'message' => 'Failed to create socket: ' . socket_strerror(socket_last_error()),
            ];
        }

        try {
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, [
                'sec' => self::TIMEOUT_SECONDS,
                'usec' => 0,
            ]);
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
                'sec' => self::TIMEOUT_SECONDS,
                'usec' => 0,
            ]);

            $result = @socket_connect($socket, 'rabbitmq', 5672);

            if ($result === false) {
                return [
                    'name' => 'rabbitmq',
                    'healthy' => false,
                    'message' => 'RabbitMQ connection failed: ' . socket_strerror(socket_last_error($socket)),
                ];
            }

            return [
                'name' => 'rabbitmq',
                'healthy' => true,
                'message' => 'RabbitMQ connection OK',
            ];
        } finally {
            socket_close($socket);
        }
    }
}
