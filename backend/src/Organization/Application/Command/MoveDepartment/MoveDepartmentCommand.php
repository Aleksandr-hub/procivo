<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\MoveDepartment;

use App\Shared\Application\Command\CommandInterface;

final readonly class MoveDepartmentCommand implements CommandInterface
{
    public function __construct(
        public string $departmentId,
        public ?string $newParentId,
    ) {
    }
}
