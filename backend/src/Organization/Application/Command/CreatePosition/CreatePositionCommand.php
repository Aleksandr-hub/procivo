<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreatePosition;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreatePositionCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $departmentId,
        public string $name,
        public ?string $description,
        public int $sortOrder,
        public bool $isHead,
    ) {
    }
}
