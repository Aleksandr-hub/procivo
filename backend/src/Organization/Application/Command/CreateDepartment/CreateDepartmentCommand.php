<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateDepartment;

use App\Shared\Application\Command\CommandInterface;

final readonly class CreateDepartmentCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public ?string $parentId,
        public string $name,
        public string $code,
        public ?string $description,
        public int $sortOrder,
    ) {
    }
}
