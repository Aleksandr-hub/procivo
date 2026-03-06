<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\RestoreEntity;

use App\Shared\Domain\SoftDeletableInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RestoreEntityHandler
{
    private const int RETENTION_DAYS = 30;

    /** @var array<string, class-string<SoftDeletableInterface>> */
    private const array ENTITY_MAP = [
        'user' => \App\Identity\Domain\Entity\User::class,
        'organization' => \App\Organization\Domain\Entity\Organization::class,
        'task' => \App\TaskManager\Domain\Entity\Task::class,
        'process_definition' => \App\Workflow\Domain\Entity\ProcessDefinition::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(RestoreEntityCommand $command): void
    {
        $entityClass = self::ENTITY_MAP[$command->entityType] ?? null;

        if (null === $entityClass) {
            throw new NotFoundHttpException(sprintf('Unknown entity type: %s', $command->entityType));
        }

        // Disable soft delete filter to find deleted entities
        $this->entityManager->getFilters()->disable('soft_delete');

        try {
            /** @var SoftDeletableInterface|null $entity */
            $entity = $this->entityManager->find($entityClass, $command->entityId);

            if (null === $entity) {
                throw new NotFoundHttpException(sprintf('Entity %s with ID %s not found.', $command->entityType, $command->entityId));
            }

            if (!$entity->isDeleted()) {
                throw new NotFoundHttpException(sprintf('Entity %s with ID %s is not deleted.', $command->entityType, $command->entityId));
            }

            $deletedAt = $entity->deletedAt();
            $retentionLimit = new \DateTimeImmutable(sprintf('-%d days', self::RETENTION_DAYS));

            if (null !== $deletedAt && $deletedAt < $retentionLimit) {
                throw new GoneHttpException(sprintf(
                    'Entity %s with ID %s was deleted more than %d days ago and cannot be restored.',
                    $command->entityType,
                    $command->entityId,
                    self::RETENTION_DAYS,
                ));
            }

            $entity->restore();
            $this->entityManager->flush();
        } finally {
            $this->entityManager->getFilters()->enable('soft_delete');
        }
    }
}
