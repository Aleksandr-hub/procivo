<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SetManager;

use App\Shared\Application\Command\CommandInterface;

final readonly class SetManagerCommand implements CommandInterface
{
    public function __construct(
        public string $employeeId,
        public ?string $managerId,
    ) {
    }
}
