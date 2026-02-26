<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\RemoveLabel;

use App\Shared\Application\Command\CommandInterface;

final readonly class RemoveLabelCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
        public string $labelId,
    ) {
    }
}
