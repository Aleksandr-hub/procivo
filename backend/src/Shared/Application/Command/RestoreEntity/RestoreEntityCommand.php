<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\RestoreEntity;

use App\Shared\Application\Command\CommandInterface;

final readonly class RestoreEntityCommand implements CommandInterface
{
    public function __construct(
        public string $entityType,
        public string $entityId,
    ) {
    }
}
