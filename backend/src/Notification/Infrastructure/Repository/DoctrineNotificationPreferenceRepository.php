<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Repository;

use App\Notification\Domain\Entity\NotificationPreference;
use App\Notification\Domain\Repository\NotificationPreferenceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(NotificationPreference $preference): void
    {
        $this->entityManager->persist($preference);
        $this->entityManager->flush();
    }

    /**
     * @return list<NotificationPreference>
     */
    public function findByUserId(string $userId): array
    {
        /** @var list<NotificationPreference> */
        return $this->entityManager->getRepository(NotificationPreference::class)->findBy(
            ['userId' => $userId],
        );
    }

    public function findByUserIdAndEventTypeAndChannel(string $userId, string $eventType, string $channel): ?NotificationPreference
    {
        /** @var NotificationPreference|null */
        return $this->entityManager->getRepository(NotificationPreference::class)->findOneBy([
            'userId' => $userId,
            'eventType' => $eventType,
            'channel' => $channel,
        ]);
    }

    public function isEnabled(string $userId, string $eventType, string $channel): bool
    {
        $preference = $this->findByUserIdAndEventTypeAndChannel($userId, $eventType, $channel);

        if (null === $preference) {
            // Default: in_app = enabled (true), email = disabled (false, opt-in)
            return 'email' !== $channel;
        }

        return $preference->enabled();
    }
}
