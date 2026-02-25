<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeletePosition;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeletePositionCommand implements CommandInterface
{
    public function __construct(
        public string $positionId,
    ) {
    }
}
