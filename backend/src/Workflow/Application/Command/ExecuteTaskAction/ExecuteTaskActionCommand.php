<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\ExecuteTaskAction;

use App\Shared\Application\Command\CommandInterface;

final readonly class ExecuteTaskActionCommand implements CommandInterface
{
    /**
     * @param array<string, mixed> $formData
     */
    public function __construct(
        public string $taskId,
        public string $actionKey,
        public array $formData = [],
    ) {
    }
}
