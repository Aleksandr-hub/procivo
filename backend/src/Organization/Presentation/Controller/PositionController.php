<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\CreatePosition\CreatePositionCommand;
use App\Organization\Application\Command\DeletePosition\DeletePositionCommand;
use App\Organization\Application\Command\UpdatePosition\UpdatePositionCommand;
use App\Organization\Application\Query\ListPositions\ListPositionsQuery;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/positions', name: 'api_v1_positions_')]
final readonly class PositionController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

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
