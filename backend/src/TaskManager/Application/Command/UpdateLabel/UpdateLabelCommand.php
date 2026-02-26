<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateLabel;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateLabelCommand implements CommandInterface
{
    public function __construct(
        public string $labelId,
        public string $name,
        public string $color,
    ) {
    }
}
