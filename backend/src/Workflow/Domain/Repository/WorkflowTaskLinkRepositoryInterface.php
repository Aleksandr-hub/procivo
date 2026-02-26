<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\WorkflowTaskLink;

interface WorkflowTaskLinkRepositoryInterface
{
    public function save(WorkflowTaskLink $link): void;

    public function findByTaskId(string $taskId): ?WorkflowTaskLink;
}
