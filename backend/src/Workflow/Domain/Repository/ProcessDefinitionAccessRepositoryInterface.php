<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\ProcessDefinitionAccess;
use App\Workflow\Domain\ValueObject\AccessType;

interface ProcessDefinitionAccessRepositoryInterface
{
    /**
     * @return list<ProcessDefinitionAccess>
     */
    public function findByProcessDefinitionId(string $processDefinitionId): array;

    /**
     * @return list<ProcessDefinitionAccess>
     */
    public function findByProcessDefinitionIdAndType(string $processDefinitionId, AccessType $type): array;

    /**
     * Returns definition IDs the user can access:
     * - definitions with no access rows for the given type (unrestricted)
     * - definitions with matching rows for user's departmentId or roleIds
     *
     * @param list<string> $roleIds
     * @return list<string>
     */
    public function findAccessibleDefinitionIds(
        string $organizationId,
        ?string $departmentId,
        array $roleIds,
        AccessType $type,
    ): array;

    public function save(ProcessDefinitionAccess $access): void;

    public function removeByProcessDefinitionId(string $processDefinitionId): void;

    public function removeByProcessDefinitionIdAndType(string $processDefinitionId, AccessType $type): void;
}
