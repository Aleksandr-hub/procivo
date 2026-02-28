<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\ValueObject\AssignmentStrategy;

final readonly class AssignmentResult
{
    public function __construct(
        public AssignmentStrategy $strategy,
        public ?string $assigneeId,
        public ?string $candidateRoleId,
        public ?string $candidateDepartmentId,
    ) {
    }
}
