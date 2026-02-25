<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateDepartment;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateDepartmentCommand implements CommandInterface
{
    public function __construct(
        public string $departmentId,
        public string $name,
        public ?string $description,
        public int $sortOrder,
    ) {
    }
}
