<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Repository;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\TaskManager\Domain\Entity\Board;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineBoardRepository implements BoardRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Board $board): void
    {
        $this->entityManager->persist($board);
        $this->entityManager->flush();
    }

    public function remove(Board $board): void
    {
        $this->entityManager->remove($board);
        $this->entityManager->flush();
    }

    public function findById(BoardId $id): ?Board
    {
        return $this->entityManager->find(Board::class, $id->value());
    }

    /**
     * @return list<Board>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array
    {
        return $this->entityManager->getRepository(Board::class)->findBy([
            'organizationId' => $organizationId->value(),
        ]);
    }
}
