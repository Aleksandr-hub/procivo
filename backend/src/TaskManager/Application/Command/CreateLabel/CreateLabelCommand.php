<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateLabel;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateLabelCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public string $color,
    ) {
    }
}
