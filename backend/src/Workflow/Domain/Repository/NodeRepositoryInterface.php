<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\Node;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;

interface NodeRepositoryInterface
{
    public function save(Node $node): void;

    public function remove(Node $node): void;

    public function findById(NodeId $id): ?Node;

    /**
     * @return list<Node>
     */
    public function findByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): array;
}
