<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\RemoveTransition;

use App\Shared\Application\Command\CommandInterface;

final readonly class RemoveTransitionCommand implements CommandInterface
{
    public function __construct(
        public string $transitionId,
    ) {
    }
}
