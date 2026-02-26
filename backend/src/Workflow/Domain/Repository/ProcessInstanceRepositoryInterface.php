<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Repository;

use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;

interface ProcessInstanceRepositoryInterface
{
    public function save(ProcessInstance $processInstance): void;

    public function findById(ProcessInstanceId $id): ?ProcessInstance;
}
