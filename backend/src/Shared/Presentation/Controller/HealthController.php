<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use App\Shared\Infrastructure\Health\DatabaseHealthCheck;
use App\Shared\Infrastructure\Health\HealthChecker;
use App\Shared\Infrastructure\Health\RabbitMqHealthCheck;
use App\Shared\Infrastructure\Health\RedisHealthCheck;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Health')]
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

    #[OA\Get(summary: 'Overall health status of all services')]
    #[OA\Response(
        response: 200,
        description: 'All services healthy',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['ok', 'degraded']),
                new OA\Property(property: 'checks', type: 'object'),
            ],
        ),
    )]
    #[OA\Response(response: 503, description: 'One or more services unhealthy')]
    #[Security(name: null)]
    #[Route('', name: 'health_overall', methods: ['GET'])]
    public function overall(): JsonResponse
    {
        $result = $this->healthChecker->checkAll();
        $statusCode = 'ok' === $result['status'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }

    #[OA\Get(summary: 'Database health check')]
    #[OA\Response(response: 200, description: 'Database healthy', content: new OA\JsonContent(properties: [new OA\Property(property: 'healthy', type: 'boolean'), new OA\Property(property: 'latency_ms', type: 'number')]))]
    #[OA\Response(response: 503, description: 'Database unhealthy')]
    #[Security(name: null)]
    #[Route('/db', name: 'health_db', methods: ['GET'])]
    public function db(): JsonResponse
    {
        $result = $this->databaseHealthCheck->check();
        $statusCode = $result['healthy'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }

    #[OA\Get(summary: 'Redis health check')]
    #[OA\Response(response: 200, description: 'Redis healthy', content: new OA\JsonContent(properties: [new OA\Property(property: 'healthy', type: 'boolean'), new OA\Property(property: 'latency_ms', type: 'number')]))]
    #[OA\Response(response: 503, description: 'Redis unhealthy')]
    #[Security(name: null)]
    #[Route('/redis', name: 'health_redis', methods: ['GET'])]
    public function redis(): JsonResponse
    {
        $result = $this->redisHealthCheck->check();
        $statusCode = $result['healthy'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }

    #[OA\Get(summary: 'RabbitMQ health check')]
    #[OA\Response(response: 200, description: 'RabbitMQ healthy', content: new OA\JsonContent(properties: [new OA\Property(property: 'healthy', type: 'boolean'), new OA\Property(property: 'latency_ms', type: 'number')]))]
    #[OA\Response(response: 503, description: 'RabbitMQ unhealthy')]
    #[Security(name: null)]
    #[Route('/rabbitmq', name: 'health_rabbitmq', methods: ['GET'])]
    public function rabbitmq(): JsonResponse
    {
        $result = $this->rabbitMqHealthCheck->check();
        $statusCode = $result['healthy'] ? 200 : 503;

        return new JsonResponse($result, $statusCode);
    }
}
