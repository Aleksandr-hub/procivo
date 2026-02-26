<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Workflow\Application\Command\AddNode\AddNodeCommand;
use App\Workflow\Application\Command\RemoveNode\RemoveNodeCommand;
use App\Workflow\Application\Command\UpdateNode\UpdateNodeCommand;
use App\Workflow\Domain\ValueObject\NodeId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/process-definitions/{definitionId}/nodes', name: 'api_v1_wf_nodes_')]
final readonly class NodeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

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
