<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\TaskManager\Domain\Entity\Label;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\ValueObject\LabelId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLabelRepository implements LabelRepositoryInterface
{
    private Connection $connection;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->connection = $entityManager->getConnection();
    }

    public function save(Label $label): void
    {
        $this->entityManager->persist($label);
        $this->entityManager->flush();
    }

    public function remove(Label $label): void
    {
        // Remove all task associations first
        $this->connection->executeStatement(
            'DELETE FROM task_manager_task_labels WHERE label_id = :labelId',
            ['labelId' => $label->id()->value()],
        );

        $this->entityManager->remove($label);
        $this->entityManager->flush();
    }

    public function findById(LabelId $id): ?Label
    {
        return $this->entityManager->find(Label::class, $id->value());
    }

    /**
     * @return list<Label>
     */
    public function findByOrganizationId(string $organizationId): array
    {
        return $this->entityManager->getRepository(Label::class)->findBy(
            ['organizationId' => $organizationId],
            ['name' => 'ASC'],
        );
    }

    public function assignToTask(LabelId $labelId, TaskId $taskId): void
    {
        // Use INSERT ... ON CONFLICT to avoid duplicates
        $this->connection->executeStatement(
            'INSERT INTO task_manager_task_labels (task_id, label_id) VALUES (:taskId, :labelId) ON CONFLICT DO NOTHING',
            [
                'taskId' => $taskId->value(),
                'labelId' => $labelId->value(),
            ],
        );
    }

    public function removeFromTask(LabelId $labelId, TaskId $taskId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM task_manager_task_labels WHERE task_id = :taskId AND label_id = :labelId',
            [
                'taskId' => $taskId->value(),
                'labelId' => $labelId->value(),
            ],
        );
    }

    /**
     * @return list<Label>
     */
    public function findByTaskId(TaskId $taskId): array
    {
        $labelIds = $this->connection->fetchFirstColumn(
            'SELECT label_id FROM task_manager_task_labels WHERE task_id = :taskId',
            ['taskId' => $taskId->value()],
        );

        if (empty($labelIds)) {
            return [];
        }

        return $this->entityManager->getRepository(Label::class)->findBy(
            ['id' => $labelIds],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function findLabelIdsByTaskIds(string ...$taskIds): array
    {
        if (empty($taskIds)) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT task_id, label_id FROM task_manager_task_labels WHERE task_id IN (?)',
            [$taskIds],
            [ArrayParameterType::STRING],
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row['task_id']][] = $row['label_id'];
        }

        return $map;
    }
}
