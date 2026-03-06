<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use App\Shared\Infrastructure\Metrics\PrometheusMetricsRenderer;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Metrics')]
final class MetricsController
{
    public function __construct(
        private readonly PrometheusMetricsRenderer $metricsRenderer,
    ) {
    }

    #[OA\Get(summary: 'Prometheus metrics endpoint')]
    #[OA\Response(response: 200, description: 'Prometheus text format metrics', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string')))]
    #[Security(name: null)]
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(
            $this->metricsRenderer->render(),
            200,
            ['Content-Type' => $this->metricsRenderer->contentType()],
        );
    }
}
