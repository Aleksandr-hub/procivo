<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Workflow\Application\Command\AddNode\AddNodeCommand;
use App\Workflow\Application\Command\RemoveNode\RemoveNodeCommand;
use App\Workflow\Application\Command\UpdateNode\UpdateNodeCommand;
use App\Workflow\Domain\ValueObject\NodeId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Designer')]
#[Route('/api/v1/organizations/{organizationId}/process-definitions/{definitionId}/nodes', name: 'api_v1_wf_nodes_')]
final readonly class NodeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Add a node to a process definition',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'name'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['start_event', 'end_event', 'user_task', 'exclusive_gateway', 'timer_event', 'sub_process']),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'config', type: 'object', description: 'Node-specific configuration'),
                    new OA\Property(property: 'position_x', type: 'number', format: 'float'),
                    new OA\Property(property: 'position_y', type: 'number', format: 'float'),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Node added', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(string $organizationId, string $definitionId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        $id = NodeId::generate()->value();

        $this->commandBus->dispatch(new AddNodeCommand(
            id: $id,
            processDefinitionId: $definitionId,
            type: $data['type'] ?? '',
            name: $data['name'] ?? '',
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
            config: isset($data['config']) && \is_array($data['config']) ? $data['config'] : [],
            positionX: isset($data['position_x']) && is_numeric($data['position_x']) ? (float) $data['position_x'] : 0.0,
            positionY: isset($data['position_y']) && is_numeric($data['position_y']) ? (float) $data['position_y'] : 0.0,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: 'Update a workflow node',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'config', type: 'object'),
                    new OA\Property(property: 'position_x', type: 'number', format: 'float'),
                    new OA\Property(property: 'position_y', type: 'number', format: 'float'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'nodeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Node updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{nodeId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $definitionId, string $nodeId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateNodeCommand(
            nodeId: $nodeId,
            name: $data['name'] ?? '',
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
            config: isset($data['config']) && \is_array($data['config']) ? $data['config'] : [],
            positionX: isset($data['position_x']) && is_numeric($data['position_x']) ? (float) $data['position_x'] : 0.0,
            positionY: isset($data['position_y']) && is_numeric($data['position_y']) ? (float) $data['position_y'] : 0.0,
        ));

        return new JsonResponse(['message' => 'Node updated.']);
    }

    #[OA\Delete(summary: 'Remove a node from a process definition')]
    #[OA\Parameter(name: 'nodeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Node removed', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{nodeId}', name: 'remove', methods: ['DELETE'])]
    public function remove(string $organizationId, string $definitionId, string $nodeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $this->commandBus->dispatch(new RemoveNodeCommand($nodeId));

        return new JsonResponse(['message' => 'Node removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
