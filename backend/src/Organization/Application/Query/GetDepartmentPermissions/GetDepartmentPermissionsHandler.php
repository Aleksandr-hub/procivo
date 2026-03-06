<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetDepartmentPermissions;

use App\Organization\Application\DTO\DepartmentPermissionDTO;
use App\Organization\Domain\Repository\DepartmentPermissionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDepartmentPermissionsHandler
{
    public function __construct(
        private DepartmentPermissionRepositoryInterface $departmentPermissionRepository,
    ) {
    }

    /**
     * @return list<DepartmentPermissionDTO>
     */
    public function __invoke(GetDepartmentPermissionsQuery $query): array
    {
        $permissions = $this->departmentPermissionRepository->findByDepartmentId(
            DepartmentId::fromString($query->departmentId),
        );

        return array_map(
            static fn ($p) => DepartmentPermissionDTO::fromEntity($p),
            $permissions,
        );
    }
}
