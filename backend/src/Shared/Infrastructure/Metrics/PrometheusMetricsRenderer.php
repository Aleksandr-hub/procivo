<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

final class PrometheusMetricsRenderer
{
    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {
    }

    public function render(): string
    {
        $renderer = new RenderTextFormat();

        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    public function contentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }
}
