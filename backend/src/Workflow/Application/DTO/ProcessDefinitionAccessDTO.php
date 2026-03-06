<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\ProcessDefinitionAccess;

final readonly class ProcessDefinitionAccessDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $processDefinitionId,
        public ?string $departmentId,
        public ?string $departmentName,
        public ?string $roleId,
        public ?string $roleName,
        public string $accessType,
    ) {
    }

    public static function fromEntity(
        ProcessDefinitionAccess $entity,
        ?string $departmentName = null,
        ?string $roleName = null,
    ): self {
        return new self(
            id: $entity->id()->value(),
            processDefinitionId: $entity->processDefinitionId(),
            departmentId: $entity->departmentId(),
            departmentName: $departmentName,
            roleId: $entity->roleId(),
            roleName: $roleName,
            accessType: $entity->accessType()->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'process_definition_id' => $this->processDefinitionId,
            'department_id' => $this->departmentId,
            'department_name' => $this->departmentName,
            'role_id' => $this->roleId,
            'role_name' => $this->roleName,
            'access_type' => $this->accessType,
        ];
    }
}
