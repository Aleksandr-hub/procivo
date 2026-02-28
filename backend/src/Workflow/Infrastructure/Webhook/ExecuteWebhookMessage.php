<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Webhook;

final readonly class ExecuteWebhookMessage
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $processInstanceId,
        public string $tokenId,
        public string $nodeId,
        public string $url,
        public string $method,
        public array $headers,
        public ?string $body,
    ) {
    }
}
