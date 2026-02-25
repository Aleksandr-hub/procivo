<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\DismissEmployee\DismissEmployeeCommand;
use App\Organization\Application\Command\HireEmployee\HireEmployeeCommand;
use App\Organization\Application\Command\SetManager\SetManagerCommand;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeCommand;
use App\Organization\Application\Query\GetEmployee\GetEmployeeQuery;
use App\Organization\Application\Query\GetOrgChart\GetOrgChartQuery;
use App\Organization\Application\Query\GetSubordinates\GetSubordinatesQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/employees', name: 'api_v1_employees_')]
final readonly class EmployeeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[Route('', name: 'hire', methods: ['POST'])]
    public function hire(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_CREATE');
        $data = $this->decodeJson($request);

        $id = EmployeeId::generate()->value();

        $this->commandBus->dispatch(new HireEmployeeCommand(
            id: $id,
            organizationId: $organizationId,
            userId: $data['user_id'] ?? '',
            positionId: $data['position_id'] ?? '',
            departmentId: $data['department_id'] ?? '',
            employeeNumber: $data['employee_number'] ?? '',
            hiredAt: $data['hired_at'] ?? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            managerId: isset($data['manager_id']) && \is_string($data['manager_id']) ? $data['manager_id'] : null,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_VIEW');

        $departmentId = $request->query->get('department_id');

        $employees = $this->queryBus->ask(new ListEmployeesQuery(
            organizationId: $organizationId,
            departmentId: \is_string($departmentId) ? $departmentId : null,
        ));

        return new JsonResponse($employees);
    }

    #[Route('/{employeeId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_VIEW');

        $dto = $this->queryBus->ask(new GetEmployeeQuery($employeeId));

        return new JsonResponse($dto);
    }

    #[Route('/{employeeId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $employeeId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateEmployeeCommand(
            employeeId: $employeeId,
            positionId: $data['position_id'] ?? null,
            departmentId: $data['department_id'] ?? null,
        ));

        return new JsonResponse(['message' => 'Employee updated.']);
    }

    #[Route('/{employeeId}/dismiss', name: 'dismiss', methods: ['POST'])]
    public function dismiss(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_DELETE');

        $this->commandBus->dispatch(new DismissEmployeeCommand($employeeId));

        return new JsonResponse(['message' => 'Employee dismissed.']);
    }

    #[Route('/{employeeId}/manager', name: 'set_manager', methods: ['PUT'])]
    public function setManager(string $organizationId, string $employeeId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new SetManagerCommand(
            employeeId: $employeeId,
            managerId: isset($data['manager_id']) && \is_string($data['manager_id']) ? $data['manager_id'] : null,
        ));

        return new JsonResponse(['message' => 'Manager updated.']);
    }

    #[Route('/{employeeId}/subordinates', name: 'subordinates', methods: ['GET'])]
    public function subordinates(string $organizationId, string $employeeId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_VIEW');

        $recursive = 'true' === $request->query->get('recursive', 'false');

        $subordinates = $this->queryBus->ask(new GetSubordinatesQuery(
            employeeId: $employeeId,
            recursive: $recursive,
        ));

        return new JsonResponse($subordinates);
    }

    #[Route('/org-chart', name: 'org_chart', methods: ['GET'], priority: 10)]
    public function orgChart(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_VIEW');

        $tree = $this->queryBus->ask(new GetOrgChartQuery($organizationId));

        return new JsonResponse($tree);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        /* @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
