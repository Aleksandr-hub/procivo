<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\AcceptInvitation\AcceptInvitationCommand;
use App\Organization\Application\DTO\InvitationDTO;
use App\Organization\Application\Query\GetInvitationByToken\GetInvitationByTokenQuery;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Invitations')]
#[Route('/api/v1/invitations', name: 'api_v1_public_invitations_')]
final readonly class PublicInvitationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[OA\Get(summary: 'Get invitation details by token (public)')]
    #[OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Invitation details', content: new OA\JsonContent(ref: new Model(type: InvitationDTO::class)))]
    #[OA\Response(response: 404, description: 'Invitation not found or expired')]
    #[Security(name: null)]
    #[Route('/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $dto = $this->queryBus->ask(new GetInvitationByTokenQuery($token));

        return new JsonResponse($dto);
    }

    #[OA\Post(
        summary: 'Accept invitation and create account (public)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'password'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Invitation accepted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 404, description: 'Invitation not found or expired')]
    #[Security(name: null)]
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
        /* @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
