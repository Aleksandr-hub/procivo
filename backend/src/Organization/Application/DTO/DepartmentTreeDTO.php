<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Department;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Department tree node with nested children')]
final readonly class DepartmentTreeDTO implements \JsonSerializable
{
    /**
     * @param list<DepartmentTreeDTO> $children
     */
    public function __construct(
        #[OA\Property(description: 'Department UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Parent department UUID', format: 'uuid', nullable: true)]
        public ?string $parentId,
        #[OA\Property(description: 'Department name')]
        public string $name,
        #[OA\Property(description: 'Unique department code')]
        public string $code,
        #[OA\Property(description: 'Department description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Sort order within parent')]
        public int $sortOrder,
        #[OA\Property(description: 'Nesting level in hierarchy')]
        public int $level,
        #[OA\Property(description: 'Department status', enum: ['active', 'archived'])]
        public string $status,
        #[OA\Property(description: 'Child departments', type: 'array', items: new OA\Items(ref: '#/components/schemas/DepartmentTreeDTO'))]
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
