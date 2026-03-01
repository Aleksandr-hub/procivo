<?php

declare(strict_types=1);

namespace App\Audit\Domain\Repository;

use App\Audit\Domain\Entity\AuditLog;

interface AuditLogRepositoryInterface
{
    /**
     * Persist an audit log entry. Append-only — no updates.
     */
    public function save(AuditLog $auditLog): void;
}
