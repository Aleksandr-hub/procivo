<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetOrgChart;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Application\DTO\OrgChartNodeDTO;
use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\Entity\Position;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetOrgChartHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private UserRepositoryInterface $userRepository,
        private PositionRepositoryInterface $positionRepository,
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    /**
     * @return list<OrgChartNodeDTO>
     */
    public function __invoke(GetOrgChartQuery $query): array
    {
        $orgId = OrganizationId::fromString($query->organizationId);

        $departments = $this->departmentRepository->findByOrganizationId($orgId);
        $employees = $this->employeeRepository->findActiveByOrganizationId($orgId);
        $positions = $this->positionRepository->findByOrganizationId($orgId);

        /** @var array<string, User> $usersMap */
        $usersMap = [];
        foreach ($employees as $emp) {
            $user = $this->userRepository->findById(UserId::fromString($emp->userId()));
            if (null !== $user) {
                $usersMap[$emp->userId()] = $user;
            }
        }

        /** @var array<string, Position> $positionsMap */
        $positionsMap = [];
        foreach ($positions as $pos) {
            $positionsMap[$pos->id()->value()] = $pos;
        }

        /** @var array<string, Department> $departmentsMap */
        $departmentsMap = [];
        foreach ($departments as $dept) {
            $departmentsMap[$dept->id()->value()] = $dept;
        }

        /** @var array<string, list<Employee>> $employeesByDept */
        $employeesByDept = [];
        foreach ($employees as $emp) {
            $employeesByDept[$emp->departmentId()->value()][] = $emp;
        }

        /** @var array<string, list<Department>> $deptsByParent */
        $deptsByParent = [];
        foreach ($departments as $dept) {
            $parentId = $dept->parentId()?->value() ?? '__root__';
            $deptsByParent[$parentId][] = $dept;
        }

        return $this->buildDepartmentLevel($deptsByParent, '__root__', $employeesByDept, $usersMap, $positionsMap, $departmentsMap);
    }

    /**
     * @param array<string, list<Department>> $deptsByParent
     * @param array<string, list<Employee>>   $employeesByDept
     * @param array<string, User>             $usersMap
     * @param array<string, Position>         $positionsMap
     * @param array<string, Department>       $departmentsMap
     *
     * @return list<OrgChartNodeDTO>
     */
    private function buildDepartmentLevel(
        array $deptsByParent,
        string $parentKey,
        array $employeesByDept,
        array $usersMap,
        array $positionsMap,
        array $departmentsMap,
    ): array {
        $result = [];

        foreach ($deptsByParent[$parentKey] ?? [] as $dept) {
            $deptId = $dept->id()->value();

            $subDepts = $this->buildDepartmentLevel(
                $deptsByParent,
                $deptId,
                $employeesByDept,
                $usersMap,
                $positionsMap,
                $departmentsMap,
            );

            $personNodes = $this->buildPersonTree(
                $employeesByDept[$deptId] ?? [],
                $usersMap,
                $positionsMap,
                $departmentsMap,
            );

            $children = array_merge($personNodes, $subDepts);

            $result[] = new OrgChartNodeDTO(
                type: 'department',
                id: $deptId,
                label: $dept->name(),
                departmentCode: $dept->code()->value(),
                children: $children,
            );
        }

        return $result;
    }

    /**
     * Build employee tree within a department using managerId hierarchy.
     * Heads are placed first. Employees whose manager is outside this department become roots.
     *
     * @param list<Employee>            $employees
     * @param array<string, User>       $usersMap
     * @param array<string, Position>   $positionsMap
     * @param array<string, Department> $departmentsMap
     *
     * @return list<OrgChartNodeDTO>
     */
    private function buildPersonTree(
        array $employees,
        array $usersMap,
        array $positionsMap,
        array $departmentsMap,
    ): array {
        if ([] === $employees) {
            return [];
        }

        $empIds = [];
        foreach ($employees as $emp) {
            $empIds[$emp->id()->value()] = true;
        }

        /** @var array<string, list<Employee>> $grouped */
        $grouped = [];
        foreach ($employees as $emp) {
            $managerId = $emp->managerId()?->value();

            if (null === $managerId || !isset($empIds[$managerId])) {
                $grouped['__root__'][] = $emp;
            } else {
                $grouped[$managerId][] = $emp;
            }
        }

        return $this->buildPersonLevel($grouped, '__root__', $usersMap, $positionsMap, $departmentsMap);
    }

    /**
     * @param array<string, list<Employee>> $grouped
     * @param array<string, User>           $usersMap
     * @param array<string, Position>       $positionsMap
     * @param array<string, Department>     $departmentsMap
     *
     * @return list<OrgChartNodeDTO>
     */
    private function buildPersonLevel(
        array $grouped,
        string $parentKey,
        array $usersMap,
        array $positionsMap,
        array $departmentsMap,
    ): array {
        $heads = [];
        $others = [];

        foreach ($grouped[$parentKey] ?? [] as $emp) {
            $pos = $positionsMap[$emp->positionId()->value()] ?? null;
            if (null !== $pos && $pos->isHead()) {
                $heads[] = $emp;
            } else {
                $others[] = $emp;
            }
        }

        $result = [];

        foreach (array_merge($heads, $others) as $emp) {
            $empId = $emp->id()->value();
            $children = $this->buildPersonLevel($grouped, $empId, $usersMap, $positionsMap, $departmentsMap);

            $user = $usersMap[$emp->userId()] ?? null;
            $pos = $positionsMap[$emp->positionId()->value()] ?? null;
            $dept = $departmentsMap[$emp->departmentId()->value()] ?? null;

            $firstName = $user?->firstName() ?? '';
            $lastName = $user?->lastName() ?? '';
            $label = trim($firstName . ' ' . $lastName);
            if ('' === $label) {
                $label = $user?->email()->value() ?? '';
            }

            $result[] = new OrgChartNodeDTO(
                type: 'person',
                id: $empId,
                label: $label,
                employeeNumber: $emp->employeeNumber()->value(),
                email: $user?->email()->value(),
                positionName: $pos?->name()->value(),
                departmentName: $dept?->name(),
                isHead: $pos?->isHead() ?? false,
                managerId: $emp->managerId()?->value(),
                children: $children,
            );
        }

        return $result;
    }
}
