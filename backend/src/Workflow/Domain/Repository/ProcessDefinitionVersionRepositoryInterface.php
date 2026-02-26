<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;

interface ProcessDefinitionVersionRepositoryInterface
{
    public function save(ProcessDefinitionVersion $version): void;

    public function findById(ProcessDefinitionVersionId $id): ?ProcessDefinitionVersion;

    /**
     * @return list<ProcessDefinitionVersion>
     */
    public function findByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): array;

    public function findLatestByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): ?ProcessDefinitionVersion;
}
