<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\TaskManager\Domain\Entity\BoardColumn;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use App\TaskManager\Domain\ValueObject\ColumnId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineBoardColumnRepository implements BoardColumnRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(BoardColumn $column): void
    {
        $this->entityManager->persist($column);
        $this->entityManager->flush();
    }

    public function remove(BoardColumn $column): void
    {
        $this->entityManager->remove($column);
        $this->entityManager->flush();
    }

    public function findById(ColumnId $id): ?BoardColumn
    {
        return $this->entityManager->find(BoardColumn::class, $id->value());
    }

    /**
     * @return list<BoardColumn>
     */
    public function findByBoardId(BoardId $boardId): array
    {
        return $this->entityManager->getRepository(BoardColumn::class)->findBy(
            ['boardId' => $boardId->value()],
            ['position' => 'ASC'],
        );
    }

    public function getMaxPosition(BoardId $boardId): int
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('MAX(c.position)')
            ->from(BoardColumn::class, 'c')
            ->where('c.boardId = :boardId')
            ->setParameter('boardId', $boardId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? -1);
    }
}
