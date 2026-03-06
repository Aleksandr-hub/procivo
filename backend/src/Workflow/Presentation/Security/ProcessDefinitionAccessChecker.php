<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Security;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Workflow\Domain\Repository\ProcessDefinitionAccessRepositoryInterface;
use App\Workflow\Domain\ValueObject\AccessType;

final readonly class ProcessDefinitionAccessChecker
{
    public function __construct(
        private ProcessDefinitionAccessRepositoryInterface $accessRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Returns the list of accessible definition IDs for the current user.
     * Returns null if the user is the organization owner (bypass = show all).
     *
     * @return list<string>|null null means owner bypass (show all)
     */
    public function getAccessibleDefinitionIds(string $organizationId, AccessType $type): ?array
    {
        $userId = $this->currentUserProvider->getUserId();

        // Organization owner bypasses access check
        if ($this->isOrganizationOwner($userId, $organizationId)) {
            return null;
        }

        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $userId,
            OrganizationId::fromString($organizationId),
        );

        if (null === $employee) {
            return [];
        }

        $departmentId = $employee->departmentId()->value();
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId($employee->id());
        $roleIds = array_map(
            static fn ($er) => $er->roleId()->value(),
            $employeeRoles,
        );

        return $this->accessRepository->findAccessibleDefinitionIds(
            $organizationId,
            $departmentId,
            $roleIds,
            $type,
        );
    }

    /**
     * Checks if the current user can start a specific process definition.
     * Returns true if:
     * - User is org owner
     * - No access rows exist for this definition with Start type (unrestricted)
     * - Access rows exist and user's dept/role matches
     */
    public function canStartDefinition(string $organizationId, string $processDefinitionId): bool
    {
        $userId = $this->currentUserProvider->getUserId();

        if ($this->isOrganizationOwner($userId, $organizationId)) {
            return true;
        }

        // Check if any start access rows exist for this definition
        $accessRows = $this->accessRepository->findByProcessDefinitionIdAndType(
            $processDefinitionId,
            AccessType::Start,
        );

        // No rows = unrestricted (backward compatible)
        if ([] === $accessRows) {
            return true;
        }

        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $userId,
            OrganizationId::fromString($organizationId),
        );

        if (null === $employee) {
            return false;
        }

        $departmentId = $employee->departmentId()->value();
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId($employee->id());
        $roleIds = array_map(
            static fn ($er) => $er->roleId()->value(),
            $employeeRoles,
        );

        // Check if any access row matches user's department or roles
        foreach ($accessRows as $row) {
            $deptMatch = null === $row->departmentId() || $row->departmentId() === $departmentId;
            $roleMatch = null === $row->roleId() || \in_array($row->roleId(), $roleIds, true);

            if ($deptMatch && $roleMatch) {
                return true;
            }
        }

        return false;
    }

    private function isOrganizationOwner(string $userId, string $organizationId): bool
    {
        $organization = $this->organizationRepository->findById(OrganizationId::fromString($organizationId));

        return null !== $organization && $organization->isOwner($userId);
    }
}
