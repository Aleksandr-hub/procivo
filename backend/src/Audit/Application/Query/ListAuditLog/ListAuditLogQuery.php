<?php

declare(strict_types=1);

namespace App\Audit\Application\Query\ListAuditLog;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListAuditLogQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $entityType = null,
        public ?string $entityId = null,
        public ?string $actorId = null,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public int $page = 1,
        public int $limit = 50,
    ) {
    }
}
