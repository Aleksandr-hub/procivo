<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Service;

use App\TaskManager\Application\DTO\AssignmentResult;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Domain\ValueObject\AssignmentStrategy;

final readonly class AssignmentResolver
{
    public function __construct(
        private OrganizationQueryPort $organizationQueryPort,
    ) {
    }

    public function resolve(
        string $strategy,
        string $organizationId,
        ?string $employeeId = null,
        ?string $roleId = null,
        ?string $departmentId = null,
    ): AssignmentResult {
        $assignmentStrategy = AssignmentStrategy::from($strategy);

        return match ($assignmentStrategy) {
            AssignmentStrategy::Unassigned => new AssignmentResult(
                AssignmentStrategy::Unassigned,
                null,
                null,
                null,
            ),
            AssignmentStrategy::SpecificUser => new AssignmentResult(
                AssignmentStrategy::SpecificUser,
                $employeeId,
                null,
                null,
            ),
            AssignmentStrategy::ByRole => $this->resolveByRole($roleId, $organizationId),
            AssignmentStrategy::ByDepartment => $this->resolveByDepartment($departmentId),
            // from_variable is pre-resolved to specific_user before CreateTaskCommand is dispatched;
            // this branch acts as a defensive fallback only.
            AssignmentStrategy::FromVariable => new AssignmentResult(
                AssignmentStrategy::Unassigned,
                null,
                null,
                null,
            ),
        };
    }

    private function resolveByRole(?string $roleId, string $organizationId): AssignmentResult
    {
        if (null === $roleId) {
            return new AssignmentResult(AssignmentStrategy::ByRole, null, null, null);
        }

        $candidates = $this->organizationQueryPort->findActiveEmployeeIdsByRoleId($roleId, $organizationId);

        if (1 === \count($candidates)) {
            return new AssignmentResult(
                AssignmentStrategy::ByRole,
                $candidates[0]['employeeId'],
                null,
                null,
            );
        }

        return new AssignmentResult(
            AssignmentStrategy::ByRole,
            null,
            $roleId,
            null,
        );
    }

    private function resolveByDepartment(?string $departmentId): AssignmentResult
    {
        if (null === $departmentId) {
            return new AssignmentResult(AssignmentStrategy::ByDepartment, null, null, null);
        }

        $candidates = $this->organizationQueryPort->findActiveEmployeeIdsByDepartmentId($departmentId);

        if (1 === \count($candidates)) {
            return new AssignmentResult(
                AssignmentStrategy::ByDepartment,
                $candidates[0]['employeeId'],
                null,
                null,
            );
        }

        return new AssignmentResult(
            AssignmentStrategy::ByDepartment,
            null,
            null,
            $departmentId,
        );
    }
}
