<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\HireEmployee;

use App\Shared\Application\Command\CommandInterface;

final readonly class HireEmployeeCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $userId,
        public string $positionId,
        public string $departmentId,
        public string $employeeNumber,
        public string $hiredAt,
        public ?string $managerId = null,
    ) {
    }
}
