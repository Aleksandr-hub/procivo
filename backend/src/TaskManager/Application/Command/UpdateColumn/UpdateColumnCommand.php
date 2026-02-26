<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateColumn;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateColumnCommand implements CommandInterface
{
    public function __construct(
        public string $columnId,
        public string $name,
        public int $position,
        public ?string $statusMapping = null,
        public ?int $wipLimit = null,
        public ?string $color = null,
    ) {
    }
}
