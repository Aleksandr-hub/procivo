<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use App\Shared\Infrastructure\Health\DatabaseHealthCheck;
use App\Shared\Infrastructure\Health\HealthChecker;
use App\Shared\Infrastructure\Health\RabbitMqHealthCheck;
use App\Shared\Infrastructure\Health\RedisHealthCheck;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health')]
final class HealthController
{
    public function __construct(
        private readonly HealthChecker $healthChecker,
        private readonly DatabaseHealthCheck $databaseHealthCheck,
        private readonly RedisHealthCheck $redisHealthCheck,
        private readonly RabbitMqHealthCheck $rabbitMqHealthCheck,
    ) {
    }

    #[Route('', name: 'health_overall', methods: ['GET'])]
    public function overall(): JsonResponse
    {
        $result = $this->healthChecker->checkAll();
        $statusCode = $result['status'] === 'ok' ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }

    #[Route('/db', name: 'health_db', methods: ['GET'])]
    public function db(): JsonResponse
    {
        $result = $this->databaseHealthCheck->check();
        $statusCode = $result['healthy'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }

    #[Route('/redis', name: 'health_redis', methods: ['GET'])]
    public function redis(): JsonResponse
    {
        $result = $this->redisHealthCheck->check();
        $statusCode = $result['healthy'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }

    #[Route('/rabbitmq', name: 'health_rabbitmq', methods: ['GET'])]
    public function rabbitmq(): JsonResponse
    {
        $result = $this->rabbitMqHealthCheck->check();
        $statusCode = $result['healthy'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }
}
