<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Department;

final readonly class DepartmentDTO
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public ?string $parentId,
        public string $name,
        public string $code,
        public ?string $description,
        public int $sortOrder,
        public int $level,
        public string $path,
        public string $status,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Department $department): self
    {
        return new self(
            id: $department->id()->value(),
            organizationId: $department->organizationId()->value(),
            parentId: $department->parentId()?->value(),
            name: $department->name(),
            code: $department->code()->value(),
            description: $department->description(),
            sortOrder: $department->sortOrder(),
            level: $department->level(),
            path: $department->path()->value(),
            status: $department->status()->value,
            createdAt: $department->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }
}
