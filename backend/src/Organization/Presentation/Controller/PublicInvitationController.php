<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\AcceptInvitation\AcceptInvitationCommand;
use App\Organization\Application\Query\GetInvitationByToken\GetInvitationByTokenQuery;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/invitations', name: 'api_v1_public_invitations_')]
final readonly class PublicInvitationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $dto = $this->queryBus->ask(new GetInvitationByTokenQuery($token));

        return new JsonResponse($dto);
    }

    #[Route('/{token}/accept', name: 'accept', methods: ['POST'])]
    public function accept(string $token, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new AcceptInvitationCommand(
            token: $token,
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
            password: $data['password'] ?? '',
        ));

        return new JsonResponse(['message' => 'Invitation accepted. You can now log in.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        /** @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
