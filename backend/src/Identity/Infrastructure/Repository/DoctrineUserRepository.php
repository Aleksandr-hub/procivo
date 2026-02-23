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
}
