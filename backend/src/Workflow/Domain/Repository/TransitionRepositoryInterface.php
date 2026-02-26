<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\Transition;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\TransitionId;

interface TransitionRepositoryInterface
{
    public function save(Transition $transition): void;

    public function remove(Transition $transition): void;

    public function findById(TransitionId $id): ?Transition;

    /**
     * @return list<Transition>
     */
    public function findByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): array;

    /**
     * @return list<Transition>
     */
    public function findBySourceNodeId(NodeId $sourceNodeId): array;
}
