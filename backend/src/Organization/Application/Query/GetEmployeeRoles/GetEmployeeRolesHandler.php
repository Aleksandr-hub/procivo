<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEmployeeRoles;

use App\Organization\Application\DTO\RoleDTO;
use App\Organization\Domain\Repository\EmployeeRoleRepositoryInterface;
use App\Organization\Domain\Repository\RoleRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetEmployeeRolesHandler
{
    public function __construct(
        private EmployeeRoleRepositoryInterface $employeeRoleRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @return list<RoleDTO>
     */
    public function __invoke(GetEmployeeRolesQuery $query): array
    {
        $employeeRoles = $this->employeeRoleRepository->findByEmployeeId(
            EmployeeId::fromString($query->employeeId),
        );

        $roles = [];
        foreach ($employeeRoles as $er) {
            $role = $this->roleRepository->findById($er->roleId());
            if (null !== $role) {
                $roles[] = RoleDTO::fromEntity($role);
            }
        }

        return $roles;
    }
}
