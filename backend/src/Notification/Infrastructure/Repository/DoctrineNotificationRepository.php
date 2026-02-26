<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Repository;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Notification $notification): void
    {
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function findById(NotificationId $id): ?Notification
    {
        return $this->entityManager->find(Notification::class, $id->value());
    }

    /**
     * @return list<Notification>
     */
    public function findByRecipientId(string $recipientId, int $limit = 50, int $offset = 0): array
    {
        return $this->entityManager->getRepository(Notification::class)->findBy(
            ['recipientId' => $recipientId],
            ['createdAt' => 'DESC'],
            $limit,
            $offset,
        );
    }

    public function countUnreadByRecipientId(string $recipientId): int
    {
        return $this->entityManager->getRepository(Notification::class)->count([
            'recipientId' => $recipientId,
            'isRead' => false,
        ]);
    }

    public function markAllAsReadByRecipientId(string $recipientId): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(Notification::class, 'n')
            ->set('n.isRead', ':read')
            ->where('n.recipientId = :recipientId')
            ->andWhere('n.isRead = :unread')
            ->setParameter('read', true)
            ->setParameter('recipientId', $recipientId)
            ->setParameter('unread', false)
            ->getQuery()
            ->execute();
    }
}
