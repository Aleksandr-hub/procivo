<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Organization\Application\Command\SuspendOrganization\SuspendOrganizationCommand;
use App\Organization\Application\Command\UpdateOrganization\UpdateOrganizationCommand;
use App\Organization\Application\DTO\OrganizationDTO;
use App\Organization\Application\Query\GetOrganization\GetOrganizationQuery;
use App\Organization\Application\Query\ListOrganizations\ListOrganizationsQuery;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Organizations')]
#[Route('/api/v1/organizations', name: 'api_v1_organizations_')]
final readonly class OrganizationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Create a new organization',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'slug'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Organization created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 400, description: 'Validation error')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        $id = OrganizationId::generate()->value();

        $this->commandBus->dispatch(new CreateOrganizationCommand(
            id: $id,
            name: $data['name'] ?? '',
            slug: $data['slug'] ?? '',
            description: $data['description'] ?? null,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Get(summary: 'List organizations for current user')]
    #[OA\Response(response: 200, description: 'List of organizations', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: OrganizationDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $organizations = $this->queryBus->ask(new ListOrganizationsQuery($user->getId()));

        return new JsonResponse($organizations);
    }

    #[OA\Get(summary: 'Get organization by ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization details', content: new OA\JsonContent(ref: new Model(type: OrganizationDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Organization not found')]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $this->authorizer->authorize($id, 'ORGANIZATION_VIEW');

        $dto = $this->queryBus->ask(new GetOrganizationQuery($id));

        return new JsonResponse($dto);
    }

    #[OA\Put(
        summary: 'Update organization',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $this->authorizer->authorize($id, 'ORGANIZATION_UPDATE');

        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateOrganizationCommand(
            organizationId: $id,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
        ));

        return new JsonResponse(['message' => 'Organization updated.']);
    }

    #[OA\Post(summary: 'Suspend organization')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization suspended', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{id}/suspend', name: 'suspend', methods: ['POST'])]
    public function suspend(string $id): JsonResponse
    {
        $this->authorizer->authorize($id, 'ORGANIZATION_DELETE');

        $this->commandBus->dispatch(new SuspendOrganizationCommand($id));

        return new JsonResponse(['message' => 'Organization suspended.']);
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
