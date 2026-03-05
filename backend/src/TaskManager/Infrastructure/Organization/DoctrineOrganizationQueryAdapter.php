<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Organization;

use App\Identity\Application\Port\AvatarStorageInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\RoleId;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final readonly class DoctrineOrganizationQueryAdapter implements OrganizationQueryPort
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private UserRepositoryInterface $userRepository,
        private AvatarStorageInterface $avatarStorage,
    ) {
    }

    /**
     * @return list<array{employeeId: string}>
     */
    public function findActiveEmployeeIdsByRoleId(string $roleId, string $organizationId): array
    {
        $employeeRoles = $this->employeeRoleRepository->findByRoleId(RoleId::fromString($roleId));

        $result = [];
        foreach ($employeeRoles as $employeeRole) {
            $employee = $this->employeeRepository->findById($employeeRole->employeeId());
            if (null !== $employee && $employee->isActive()) {
                $result[] = ['employeeId' => $employee->id()->value()];
            }
        }

        return $result;
    }

    /**
     * @return list<array{employeeId: string}>
     */
    public function findActiveEmployeeIdsByDepartmentId(string $departmentId): array
    {
        $employees = $this->employeeRepository->findByDepartmentId(DepartmentId::fromString($departmentId));

        $result = [];
        foreach ($employees as $employee) {
            if ($employee->isActive()) {
                $result[] = ['employeeId' => $employee->id()->value()];
            }
        }

        return $result;
    }

    public function employeeBelongsToRole(string $employeeId, string $roleId): bool
    {
        return null !== $this->employeeRoleRepository->findByEmployeeIdAndRoleId(
            EmployeeId::fromString($employeeId),
            RoleId::fromString($roleId),
        );
    }

    public function employeeBelongsToDepartment(string $employeeId, string $departmentId): bool
    {
        $employee = $this->employeeRepository->findById(EmployeeId::fromString($employeeId));

        return null !== $employee && $employee->departmentId()->value() === $departmentId;
    }

    /**
     * @return list<string>
     */
    public function getEmployeeRoleIds(string $employeeId): array
    {
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId(EmployeeId::fromString($employeeId));

        return array_map(
            static fn ($er) => $er->roleId()->value(),
            $employeeRoles,
        );
    }

    public function getEmployeeDepartmentId(string $employeeId): ?string
    {
        $employee = $this->employeeRepository->findById(EmployeeId::fromString($employeeId));

        return $employee?->departmentId()->value();
    }

    /**
     * @param list<string> $employeeIds
     *
     * @return array<string, array{name: string, avatarUrl: string|null}> Map of employeeId => {name, avatarUrl}
     */
    public function resolveEmployeeDisplayNames(array $employeeIds): array
    {
        $map = [];

        foreach ($employeeIds as $employeeId) {
            if (!SymfonyUuid::isValid($employeeId)) {
                continue;
            }

            $employee = $this->employeeRepository->findById(EmployeeId::fromString($employeeId));
            if (null === $employee) {
                continue;
            }

            $user = $this->userRepository->findById(UserId::fromString($employee->userId()));
            if (null === $user) {
                continue;
            }

            $fullName = trim($user->firstName() . ' ' . $user->lastName());
            $name = '' !== $fullName ? $fullName : $user->email()->value();

            $avatarUrl = null;
            if (null !== $user->avatarPath()) {
                $avatarUrl = $this->avatarStorage->getUrl($user->avatarPath());
            }

            $map[$employeeId] = [
                'name' => $name,
                'avatarUrl' => $avatarUrl,
            ];
        }

        return $map;
    }
}
