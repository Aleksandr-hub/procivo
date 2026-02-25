<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdatePosition;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdatePositionCommand implements CommandInterface
{
    public function __construct(
        public string $positionId,
        public string $name,
        public ?string $description,
        public int $sortOrder,
        public bool $isHead,
    ) {
    }
}
