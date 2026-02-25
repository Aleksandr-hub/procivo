<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Department;

final readonly class DepartmentTreeDTO implements \JsonSerializable
{
    /**
     * @param list<DepartmentTreeDTO> $children
     */
    public function __construct(
        public string $id,
        public ?string $parentId,
        public string $name,
        public string $code,
        public ?string $description,
        public int $sortOrder,
        public int $level,
        public string $status,
        public array $children,
    ) {
    }

    /**
     * @param list<Department> $departments
     *
     * @return list<DepartmentTreeDTO>
     */
    public static function buildTree(array $departments): array
    {
        $grouped = [];
        foreach ($departments as $dept) {
            $parentId = $dept->parentId()?->value();
            $grouped[$parentId ?? '__root__'][] = $dept;
        }

        return self::buildLevel($grouped, '__root__');
    }

    /**
     * @param array<string, list<Department>> $grouped
     *
     * @return list<DepartmentTreeDTO>
     */
    private static function buildLevel(array $grouped, string $parentKey): array
    {
        $result = [];

        foreach ($grouped[$parentKey] ?? [] as $dept) {
            $deptId = $dept->id()->value();
            $children = self::buildLevel($grouped, $deptId);

            $result[] = new self(
                id: $deptId,
                parentId: $dept->parentId()?->value(),
                name: $dept->name(),
                code: $dept->code()->value(),
                description: $dept->description(),
                sortOrder: $dept->sortOrder(),
                level: $dept->level(),
                status: $dept->status()->value,
                children: $children,
            );
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'parentId' => $this->parentId,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'sortOrder' => $this->sortOrder,
            'level' => $this->level,
            'status' => $this->status,
            'children' => $this->children,
        ];
    }
}
