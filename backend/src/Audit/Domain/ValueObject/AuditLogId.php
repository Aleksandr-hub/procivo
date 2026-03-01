<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class AuditLogId extends Uuid
{
}
