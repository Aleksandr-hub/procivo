<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Controller;

use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Command\RestoreEntity\RestoreEntityCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin', name: 'api_v1_admin_shared_')]
final readonly class AdminRestoreController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

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
