<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeleteDepartment;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteDepartmentCommand implements CommandInterface
{
    public function __construct(
        public string $departmentId,
    ) {
    }
}
