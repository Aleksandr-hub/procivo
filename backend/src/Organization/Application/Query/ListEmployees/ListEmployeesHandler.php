<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployees;

use App\Organization\Application\DTO\EmployeeDTO;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListEmployeesHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    /**
     * @return list<EmployeeDTO>
     */
    public function __invoke(ListEmployeesQuery $query): array
    {
        $employees = null !== $query->departmentId
            ? $this->employeeRepository->findByDepartmentId(DepartmentId::fromString($query->departmentId))
            : $this->employeeRepository->findByOrganizationId(OrganizationId::fromString($query->organizationId));

        return array_map(
            static fn ($emp) => EmployeeDTO::fromEntity($emp),
            $employees,
        );
    }
}
