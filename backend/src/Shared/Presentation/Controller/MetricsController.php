<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use App\Shared\Infrastructure\Metrics\PrometheusMetricsRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MetricsController
{
    public function __construct(
        private readonly PrometheusMetricsRenderer $metricsRenderer,
    ) {
    }

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
