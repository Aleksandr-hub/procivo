<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\DismissEmployee\DismissEmployeeCommand;
use App\Organization\Application\Command\HireEmployee\HireEmployeeCommand;
use App\Organization\Application\Command\SetManager\SetManagerCommand;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeCommand;
use App\Organization\Application\DTO\EmployeeDTO;
use App\Organization\Application\DTO\OrgChartNodeDTO;
use App\Organization\Application\Query\GetEmployee\GetEmployeeQuery;
use App\Organization\Application\Query\GetOrgChart\GetOrgChartQuery;
use App\Organization\Application\Query\GetSubordinates\GetSubordinatesQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Employees')]
#[Route('/api/v1/organizations/{organizationId}/employees', name: 'api_v1_employees_')]
final readonly class EmployeeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Hire a new employee',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'position_id', 'department_id', 'employee_number'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'position_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'department_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'employee_number', type: 'string'),
                    new OA\Property(property: 'hired_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'manager_id', type: 'string', format: 'uuid', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Employee hired', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    #[OA\Get(summary: 'List employees in organization')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'department_id', in: 'query', description: 'Filter by department', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Employee list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: EmployeeDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    #[OA\Get(summary: 'Get employee by ID')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Employee details', content: new OA\JsonContent(ref: new Model(type: EmployeeDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{employeeId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_VIEW');

        $dto = $this->queryBus->ask(new GetEmployeeQuery($employeeId));

        return new JsonResponse($dto);
    }

    #[OA\Put(
        summary: 'Update employee details',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'position_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'department_id', type: 'string', format: 'uuid', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Employee updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    #[OA\Post(summary: 'Dismiss employee')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Employee dismissed', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{employeeId}/dismiss', name: 'dismiss', methods: ['POST'])]
    public function dismiss(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'EMPLOYEE_DELETE');

        $this->commandBus->dispatch(new DismissEmployeeCommand($employeeId));

        return new JsonResponse(['message' => 'Employee dismissed.']);
    }

    #[OA\Put(
        summary: 'Set employee manager',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'manager_id', type: 'string', format: 'uuid', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Manager updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    #[OA\Get(summary: 'Get employee subordinates')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'recursive', in: 'query', description: 'Include indirect subordinates', schema: new OA\Schema(type: 'boolean', default: false))]
    #[OA\Response(response: 200, description: 'Subordinate list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: EmployeeDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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

    #[OA\Get(summary: 'Get organization chart')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization chart tree', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: OrgChartNodeDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
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
