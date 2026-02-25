<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Repository;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager->find(User::class, $id->value());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email->value(),
        ]);
    }

    public function existsByEmail(Email $email): bool
    {
        return null !== $this->findByEmail($email);
    }

    public function search(string $term, int $limit): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('u.firstName', 'ASC')
            ->setMaxResults($limit);

        if ('' !== $term) {
            $qb->andWhere('LOWER(u.email) LIKE :term OR LOWER(u.firstName) LIKE :term OR LOWER(u.lastName) LIKE :term')
                ->setParameter('term', '%'.mb_strtolower($term).'%');
        }

        return $qb->getQuery()->getResult();
    }
}
