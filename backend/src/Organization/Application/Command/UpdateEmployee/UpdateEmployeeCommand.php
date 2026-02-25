<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateEmployee;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateEmployeeCommand implements CommandInterface
{
    public function __construct(
        public string $employeeId,
        public ?string $positionId,
        public ?string $departmentId,
    ) {
    }
}
