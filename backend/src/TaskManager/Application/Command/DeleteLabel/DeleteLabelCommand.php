<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteLabel;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteLabelCommand implements CommandInterface
{
    public function __construct(
        public string $labelId,
    ) {
    }
}
