<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Workflow\Domain\Event\NotificationNodeActivatedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnNotificationNodeActivated
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(NotificationNodeActivatedEvent $event): void
    {
        $config = $event->notificationConfig;
        $recipientType = (string) ($config['recipient_type'] ?? 'initiator');

        $recipientIds = $this->resolveRecipients($recipientType, $config, $event);
        $body = $this->interpolateTemplate((string) ($config['template'] ?? ''), $event->variables);
        $title = '' !== $body ? mb_substr($body, 0, 100) : 'Process notification';

        foreach ($recipientIds as $recipientId) {
            $notification = Notification::create(
                NotificationId::generate(),
                $recipientId,
                NotificationType::ProcessNotification,
                $title,
                $body ?: 'Process notification',
                $event->processInstanceId,
            );
            $this->notificationRepository->save($notification);
        }
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return list<string>
     */
    private function resolveRecipients(string $recipientType, array $config, NotificationNodeActivatedEvent $event): array
    {
        return match ($recipientType) {
            'specific' => array_filter([(string) ($config['recipient_value'] ?? '')]),
            default => [$event->startedBy], // 'initiator' — process starter
        };
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function interpolateTemplate(string $template, array $variables): string
    {
        if ('' === $template) {
            return '';
        }

        return preg_replace_callback('/\{\{(\w+)\}\}/', static function (array $matches) use ($variables): string {
            $key = $matches[1];

            return isset($variables[$key]) ? (string) $variables[$key] : $matches[0];
        }, $template) ?? $template;
    }
}
