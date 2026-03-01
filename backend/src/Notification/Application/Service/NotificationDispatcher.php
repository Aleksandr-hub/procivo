<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Application\DTO\NotificationDTO;
use App\Notification\Application\Port\NotificationMailerInterface;
use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Repository\NotificationPreferenceRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Mercure\NotificationMercurePublisher;

final readonly class NotificationDispatcher
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
        private NotificationMercurePublisher $mercurePublisher,
        private NotificationMailerInterface $notificationMailer,
    ) {
    }

    /**
     * Dispatch a notification to a recipient.
     *
     * $params keys:
     *   - recipientId: string
     *   - recipientEmail: ?string
     *   - type: NotificationType
     *   - title: string
     *   - body: string
     *   - relatedEntityId: ?string
     *   - relatedEntityType: ?string
     *   - emailSubject: ?string
     *   - emailTemplate: ?string
     *   - emailContext: ?array<string, mixed>
     *
     * @param array{recipientId: string, recipientEmail?: ?string, type: NotificationType, title: string, body: string, relatedEntityId?: ?string, relatedEntityType?: ?string, emailSubject?: ?string, emailTemplate?: ?string, emailContext?: array<string, mixed>} $params
     */
    public function dispatch(array $params): void
    {
        $recipientId = $params['recipientId'];
        $type = $params['type'];

        // 1. Check in_app preference (default: enabled)
        if ($this->preferenceRepository->isEnabled($recipientId, $type->value, 'in_app')) {
            $notification = Notification::create(
                NotificationId::generate(),
                $recipientId,
                $type,
                $params['title'],
                $params['body'],
                $params['relatedEntityId'] ?? null,
                'in_app',
                $params['relatedEntityType'] ?? null,
            );

            $this->notificationRepository->save($notification);

            // Publish Mercure SSE update for real-time delivery
            $this->mercurePublisher->publishNotification(
                $recipientId,
                NotificationDTO::fromEntity($notification)->jsonSerialize(),
            );
        }

        // 2. Check email preference (default: disabled — opt-in)
        $recipientEmail = $params['recipientEmail'] ?? null;
        $emailTemplate = $params['emailTemplate'] ?? null;

        if (
            null !== $recipientEmail
            && null !== $emailTemplate
            && $this->preferenceRepository->isEnabled($recipientId, $type->value, 'email')
        ) {
            $this->notificationMailer->send(
                $recipientEmail,
                $params['emailSubject'] ?? $params['title'],
                $emailTemplate,
                $params['emailContext'] ?? [],
            );
        }
    }
}
