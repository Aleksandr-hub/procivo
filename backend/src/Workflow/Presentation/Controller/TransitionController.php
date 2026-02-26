<?php

declare(strict_types=1);

namespace App\Workflow\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Workflow\Application\Command\AddTransition\AddTransitionCommand;
use App\Workflow\Application\Command\RemoveTransition\RemoveTransitionCommand;
use App\Workflow\Application\Command\UpdateTransition\UpdateTransitionCommand;
use App\Workflow\Domain\ValueObject\TransitionId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/process-definitions/{definitionId}/transitions', name: 'api_v1_wf_transitions_')]
final readonly class TransitionController
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
