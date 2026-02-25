<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetDepartmentTree;

use App\Organization\Application\DTO\DepartmentTreeDTO;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDepartmentTreeHandler
{
    public function __construct(
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    /**
     * @return list<DepartmentTreeDTO>
     */
    public function __invoke(GetDepartmentTreeQuery $query): array
    {
        $departments = $this->departmentRepository->findByOrganizationId(
            OrganizationId::fromString($query->organizationId),
        );

        return DepartmentTreeDTO::buildTree($departments);
    }
}
