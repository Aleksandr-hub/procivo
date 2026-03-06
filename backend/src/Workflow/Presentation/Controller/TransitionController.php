<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Workflow\Application\Command\AddTransition\AddTransitionCommand;
use App\Workflow\Application\Command\RemoveTransition\RemoveTransitionCommand;
use App\Workflow\Application\Command\UpdateTransition\UpdateTransitionCommand;
use App\Workflow\Domain\ValueObject\TransitionId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Designer')]
#[Route('/api/v1/organizations/{organizationId}/process-definitions/{definitionId}/transitions', name: 'api_v1_wf_transitions_')]
final readonly class TransitionController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Add a transition to a process definition',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['source_node_id', 'target_node_id'],
                properties: [
                    new OA\Property(property: 'source_node_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'target_node_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string', nullable: true),
                    new OA\Property(property: 'action_key', type: 'string', nullable: true),
                    new OA\Property(property: 'condition_expression', type: 'string', nullable: true),
                    new OA\Property(property: 'form_fields', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Transition added', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(string $organizationId, string $definitionId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        $id = TransitionId::generate()->value();

        $this->commandBus->dispatch(new AddTransitionCommand(
            id: $id,
            processDefinitionId: $definitionId,
            sourceNodeId: $data['source_node_id'] ?? '',
            targetNodeId: $data['target_node_id'] ?? '',
            name: isset($data['name']) && \is_string($data['name']) ? $data['name'] : null,
            actionKey: isset($data['action_key']) && \is_string($data['action_key']) ? $data['action_key'] : null,
            conditionExpression: isset($data['condition_expression']) && \is_string($data['condition_expression']) ? $data['condition_expression'] : null,
            formFields: isset($data['form_fields']) && \is_array($data['form_fields']) ? $data['form_fields'] : null,
            sortOrder: isset($data['sort_order']) && is_numeric($data['sort_order']) ? (int) $data['sort_order'] : 0,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: 'Update a workflow transition',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', nullable: true),
                new OA\Property(property: 'action_key', type: 'string', nullable: true),
                new OA\Property(property: 'condition_expression', type: 'string', nullable: true),
                new OA\Property(property: 'form_fields', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
                new OA\Property(property: 'sort_order', type: 'integer'),
            ]),
        ),
    )]
    #[OA\Parameter(name: 'transitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Transition updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{transitionId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $definitionId, string $transitionId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateTransitionCommand(
            transitionId: $transitionId,
            name: isset($data['name']) && \is_string($data['name']) ? $data['name'] : null,
            actionKey: isset($data['action_key']) && \is_string($data['action_key']) ? $data['action_key'] : null,
            conditionExpression: isset($data['condition_expression']) && \is_string($data['condition_expression']) ? $data['condition_expression'] : null,
            formFields: isset($data['form_fields']) && \is_array($data['form_fields']) ? $data['form_fields'] : null,
            sortOrder: isset($data['sort_order']) && is_numeric($data['sort_order']) ? (int) $data['sort_order'] : 0,
        ));

        return new JsonResponse(['message' => 'Transition updated.']);
    }

    #[OA\Delete(summary: 'Remove a transition from a process definition')]
    #[OA\Parameter(name: 'transitionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Transition removed', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{transitionId}', name: 'remove', methods: ['DELETE'])]
    public function remove(string $organizationId, string $definitionId, string $transitionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'WORKFLOW_UPDATE');

        $this->commandBus->dispatch(new RemoveTransitionCommand($transitionId));

        return new JsonResponse(['message' => 'Transition removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
