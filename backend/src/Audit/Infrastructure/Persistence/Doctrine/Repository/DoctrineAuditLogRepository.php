<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Doctrine\Repository;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAuditLogRepository implements AuditLogRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(AuditLog $auditLog): void
    {
        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }
}
