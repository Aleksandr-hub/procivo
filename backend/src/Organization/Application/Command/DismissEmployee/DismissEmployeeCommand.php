<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DismissEmployee;

use App\Shared\Application\Command\CommandInterface;

final readonly class DismissEmployeeCommand implements CommandInterface
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
