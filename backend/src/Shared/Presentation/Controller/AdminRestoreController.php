<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Command\RestoreEntity\RestoreEntityCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Admin')]
#[Route('/api/v1/admin', name: 'api_v1_admin_shared_')]
final readonly class AdminRestoreController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[OA\Post(
        summary: 'Restore a soft-deleted entity',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['entity_type', 'entity_id'],
                properties: [
                    new OA\Property(property: 'entity_type', type: 'string', description: 'Entity type to restore', example: 'organization'),
                    new OA\Property(property: 'entity_id', type: 'string', format: 'uuid', description: 'Entity UUID'),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Entity restored', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 400, description: 'Missing entity_type or entity_id')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 404, description: 'Entity not found')]
    #[Route('/restore', name: 'restore', methods: ['POST'])]
    public function restore(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $entityType = $data['entity_type'] ?? '';
        $entityId = $data['entity_id'] ?? '';

        if ('' === $entityType || '' === $entityId) {
            return new JsonResponse(
                ['error' => 'entity_type and entity_id are required'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $this->commandBus->dispatch(new RestoreEntityCommand(
            entityType: $entityType,
            entityId: $entityId,
        ));

        return new JsonResponse(['message' => 'Entity restored successfully'], Response::HTTP_OK);
    }
}
