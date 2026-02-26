<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\TaskManager\Domain\Entity\TaskAttachment;
use App\TaskManager\Domain\Repository\TaskAttachmentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\AttachmentId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TaskAttachment $attachment): void
    {
        $this->entityManager->persist($attachment);
        $this->entityManager->flush();
    }

    public function remove(TaskAttachment $attachment): void
    {
        $this->entityManager->remove($attachment);
        $this->entityManager->flush();
    }

    public function findById(AttachmentId $id): ?TaskAttachment
    {
        return $this->entityManager->find(TaskAttachment::class, $id->value());
    }

    /**
     * @return list<TaskAttachment>
     */
    public function findByTaskId(TaskId $taskId): array
    {
        return $this->entityManager->getRepository(TaskAttachment::class)->findBy(
            ['taskId' => $taskId->value()],
            ['uploadedAt' => 'DESC'],
        );
    }
}
