<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\TaskManager\Domain\Entity\TaskAssignment;
use App\TaskManager\Domain\Repository\TaskAssignmentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTaskAssignmentRepository implements TaskAssignmentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TaskAssignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();
    }

    public function remove(TaskAssignment $assignment): void
    {
        $this->entityManager->remove($assignment);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?TaskAssignment
    {
        return $this->entityManager->find(TaskAssignment::class, $id);
    }

    /**
     * @return list<TaskAssignment>
     */
    public function findByTaskId(TaskId $taskId): array
    {
        return $this->entityManager->getRepository(TaskAssignment::class)->findBy(
            ['taskId' => $taskId->value()],
            ['assignedAt' => 'ASC'],
        );
    }

    public function findByTaskAndEmployee(TaskId $taskId, string $employeeId, string $role): ?TaskAssignment
    {
        return $this->entityManager->getRepository(TaskAssignment::class)->findOneBy([
            'taskId' => $taskId->value(),
            'employeeId' => $employeeId,
            'role' => $role,
        ]);
    }

    public function removeAllByTaskId(TaskId $taskId): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM task_manager_task_assignments WHERE task_id = :taskId',
            ['taskId' => $taskId->value()],
        );
    }
}
