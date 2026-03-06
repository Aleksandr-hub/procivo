<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class HealthChecker
{
    /** @var iterable<HealthCheckInterface> */
    private readonly iterable $checks;

    public function __construct(
        #[TaggedIterator('app.health_check')]
        iterable $checks,
    ) {
        $this->checks = $checks;
    }

    /**
     * @return array{status: string, checks: array<string, array{name: string, healthy: bool, message: string}>}
     */
    public function checkAll(): array
    {
        $results = [];
        $allHealthy = true;

        foreach ($this->checks as $check) {
            $result = $check->check();
            $results[$result['name']] = $result;

            if (!$result['healthy']) {
                $allHealthy = false;
            }
        }

        return [
            'status' => $allHealthy ? 'ok' : 'degraded',
            'checks' => $results,
        ];
    }
}
