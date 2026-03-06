<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\CreateDepartment\CreateDepartmentCommand;
use App\Organization\Application\Command\DeleteDepartment\DeleteDepartmentCommand;
use App\Organization\Application\Command\MoveDepartment\MoveDepartmentCommand;
use App\Organization\Application\Command\UpdateDepartment\UpdateDepartmentCommand;
use App\Organization\Application\DTO\DepartmentDTO;
use App\Organization\Application\DTO\DepartmentTreeDTO;
use App\Organization\Application\Query\GetDepartment\GetDepartmentQuery;
use App\Organization\Application\Query\GetDepartmentTree\GetDepartmentTreeQuery;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Departments')]
#[Route('/api/v1/organizations/{organizationId}/departments', name: 'api_v1_departments_')]
final readonly class DepartmentController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Create a department',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'code', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'parent_id', type: 'string', format: 'uuid', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Department created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'DEPARTMENT_CREATE');
        $data = $this->decodeJson($request);

        $id = DepartmentId::generate()->value();

        $this->commandBus->dispatch(new CreateDepartmentCommand(
            id: $id,
            organizationId: $organizationId,
            parentId: $data['parent_id'] ?? null,
            name: $data['name'] ?? '',
            code: $data['code'] ?? '',
            description: $data['description'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Get(summary: 'Get department tree')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Department tree', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: DepartmentTreeDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/tree', name: 'tree', methods: ['GET'])]
    public function tree(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'DEPARTMENT_VIEW');

        $tree = $this->queryBus->ask(new GetDepartmentTreeQuery($organizationId));

        return new JsonResponse($tree);
    }

    #[OA\Get(summary: 'Get department by ID')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Department details', content: new OA\JsonContent(ref: new Model(type: DepartmentDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{departmentId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $departmentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'DEPARTMENT_VIEW');

        $dto = $this->queryBus->ask(new GetDepartmentQuery($departmentId));

        return new JsonResponse($dto);
    }

    #[OA\Put(
        summary: 'Update department',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Department updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{departmentId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $departmentId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'DEPARTMENT_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateDepartmentCommand(
            departmentId: $departmentId,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
        ));

        return new JsonResponse(['message' => 'Department updated.']);
    }

    #[OA\Post(
        summary: 'Move department to a new parent',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'new_parent_id', type: 'string', format: 'uuid', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Department moved', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{departmentId}/move', name: 'move', methods: ['POST'])]
    public function move(string $organizationId, string $departmentId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'DEPARTMENT_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new MoveDepartmentCommand(
            departmentId: $departmentId,
            newParentId: $data['new_parent_id'] ?? null,
        ));

        return new JsonResponse(['message' => 'Department moved.']);
    }

    #[OA\Delete(summary: 'Delete department')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Department deleted')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{departmentId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $departmentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'DEPARTMENT_DELETE');

        $this->commandBus->dispatch(new DeleteDepartmentCommand($departmentId));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
