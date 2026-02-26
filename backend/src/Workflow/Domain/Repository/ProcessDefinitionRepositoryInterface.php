<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\ProcessDefinition;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionStatus;

interface ProcessDefinitionRepositoryInterface
{
    public function save(ProcessDefinition $processDefinition): void;

    public function remove(ProcessDefinition $processDefinition): void;

    public function findById(ProcessDefinitionId $id): ?ProcessDefinition;

    /**
     * @return list<ProcessDefinition>
     */
    public function findByOrganizationId(string $organizationId, ?ProcessDefinitionStatus $status = null): array;
}
