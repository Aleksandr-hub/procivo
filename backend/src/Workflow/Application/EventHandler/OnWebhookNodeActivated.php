<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Workflow\Domain\Event\WebhookNodeActivatedEvent;
use App\Workflow\Infrastructure\Webhook\ExecuteWebhookMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnWebhookNodeActivated
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(WebhookNodeActivatedEvent $event): void
    {
        $config = $event->webhookConfig;
        $url = (string) ($config['url'] ?? '');

        if ('' === $url) {
            return;
        }

        $method = strtoupper((string) ($config['method'] ?? 'POST'));
        $headers = $this->buildHeaders($config);
        $body = $this->interpolateBody($config, $event->variables);

        $this->messageBus->dispatch(new ExecuteWebhookMessage(
            processInstanceId: $event->processInstanceId,
            tokenId: $event->tokenId,
            nodeId: $event->nodeId,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        ));
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, string>
     */
    private function buildHeaders(array $config): array
    {
        $headers = ['Content-Type' => 'application/json'];

        /** @var list<array{key: string, value: string}> $configHeaders */
        $configHeaders = $config['headers'] ?? [];
        foreach ($configHeaders as $header) {
            if ('' !== $header['key']) {
                $headers[$header['key']] = $header['value'];
            }
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $variables
     */
    private function interpolateBody(array $config, array $variables): ?string
    {
        $bodyTemplate = (string) ($config['body_template'] ?? '');

        if ('' === $bodyTemplate) {
            return json_encode($variables, \JSON_THROW_ON_ERROR) ?: null;
        }

        return preg_replace_callback('/\{\{(\w+)\}\}/', static function (array $matches) use ($variables): string {
            $key = $matches[1];

            return isset($variables[$key]) ? (string) $variables[$key] : $matches[0];
        }, $bodyTemplate) ?? $bodyTemplate;
    }
}
