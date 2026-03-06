<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\CreatePosition\CreatePositionCommand;
use App\Organization\Application\Command\DeletePosition\DeletePositionCommand;
use App\Organization\Application\Command\UpdatePosition\UpdatePositionCommand;
use App\Organization\Application\DTO\PositionDTO;
use App\Organization\Application\Query\ListPositions\ListPositionsQuery;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Positions')]
#[Route('/api/v1/organizations/{organizationId}/positions', name: 'api_v1_positions_')]
final readonly class PositionController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Create a position',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['department_id', 'name'],
                properties: [
                    new OA\Property(property: 'department_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                    new OA\Property(property: 'is_head', type: 'boolean', default: false),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Position created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'POSITION_CREATE');
        $data = $this->decodeJson($request);

        $id = PositionId::generate()->value();

        $this->commandBus->dispatch(new CreatePositionCommand(
            id: $id,
            organizationId: $organizationId,
            departmentId: $data['department_id'] ?? '',
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isHead: (bool) ($data['is_head'] ?? false),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Get(summary: 'List positions in organization')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'department_id', in: 'query', description: 'Filter by department', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Position list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: PositionDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'POSITION_VIEW');

        $departmentId = $request->query->get('department_id');

        $positions = $this->queryBus->ask(new ListPositionsQuery(
            organizationId: $organizationId,
            departmentId: \is_string($departmentId) ? $departmentId : null,
        ));

        return new JsonResponse($positions);
    }

    #[OA\Put(
        summary: 'Update position',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                    new OA\Property(property: 'is_head', type: 'boolean', default: false),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'positionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Position updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{positionId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $positionId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'POSITION_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdatePositionCommand(
            positionId: $positionId,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isHead: (bool) ($data['is_head'] ?? false),
        ));

        return new JsonResponse(['message' => 'Position updated.']);
    }

    #[OA\Delete(summary: 'Delete position')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'positionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Position deleted')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{positionId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $positionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'POSITION_DELETE');

        $this->commandBus->dispatch(new DeletePositionCommand($positionId));

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
