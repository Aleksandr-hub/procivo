<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class MetricsCollector implements EventSubscriberInterface
{
    private const REQUEST_DURATION_HISTOGRAM = 'procivo_http_request_duration_seconds';
    private const REQUEST_TOTAL_COUNTER = 'procivo_http_requests_total';
    private const ERROR_TOTAL_COUNTER = 'procivo_http_errors_total';

    private const DURATION_BUCKETS = [0.01, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5];

    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            KernelEvents::TERMINATE => ['onKernelTerminate', -1000],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set('_metrics_start_time', microtime(true));
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $startTime = $request->attributes->get('_metrics_start_time');
        if ($startTime === null) {
            return;
        }

        $duration = microtime(true) - (float) $startTime;
        $method = $request->getMethod();
        $route = $request->attributes->get('_route', 'unknown');
        $status = (string) $response->getStatusCode();

        // Request duration histogram
        $histogram = $this->registry->getOrRegisterHistogram(
            '',
            self::REQUEST_DURATION_HISTOGRAM,
            'HTTP request duration in seconds',
            ['method', 'route', 'status'],
            self::DURATION_BUCKETS,
        );
        $histogram->observe($duration, [$method, $route, $status]);

        // Total requests counter
        $counter = $this->registry->getOrRegisterCounter(
            '',
            self::REQUEST_TOTAL_COUNTER,
            'Total HTTP requests',
            ['method', 'route', 'status'],
        );
        $counter->inc([$method, $route, $status]);

        // Error counter (4xx and 5xx)
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $errorCounter = $this->registry->getOrRegisterCounter(
                '',
                self::ERROR_TOTAL_COUNTER,
                'Total HTTP errors (4xx and 5xx)',
                ['method', 'route', 'status'],
            );
            $errorCounter->inc([$method, $route, $status]);
        }
    }
}
