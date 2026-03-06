<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetMyPermissions;

use App\Organization\Application\DTO\RoleDTO;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Infrastructure\Security\Service\PermissionResolverInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetMyPermissionsHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private RoleRepositoryInterface $roleRepository,
        private PermissionResolverInterface $permissionResolver,
    ) {
    }

    /**
     * @return array{isOwner: bool, roles: list<RoleDTO>, permissions: list<array{resource: string, action: string, scope: string}>}
     */
    public function __invoke(GetMyPermissionsQuery $query): array
    {
        $organizationId = OrganizationId::fromString($query->organizationId);
        $organization = $this->organizationRepository->findById($organizationId);

        $isOwner = null !== $organization && $organization->isOwner($query->userId);

        $employee = $this->employeeRepository->findByUserIdAndOrganizationId(
            $query->userId,
            $organizationId,
        );

        if (null === $employee) {
            return [
                'isOwner' => $isOwner,
                'roles' => [],
                'permissions' => [],
            ];
        }

        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId($employee->id());

        $roles = [];
        foreach ($employeeRoles as $er) {
            $role = $this->roleRepository->findById($er->roleId());
            if (null !== $role) {
                $roles[] = RoleDTO::fromEntity($role);
            }
        }

        $permissions = $this->permissionResolver->resolveEffectivePermissions(
            $query->userId,
            $query->organizationId,
        );

        return [
            'isOwner' => $isOwner,
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }
}
