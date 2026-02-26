<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AssignLabel;

use App\Shared\Application\Command\CommandInterface;

final readonly class AssignLabelCommand implements CommandInterface
{
    public function __construct(
        public string $taskId,
        public string $labelId,
    ) {
    }
}
