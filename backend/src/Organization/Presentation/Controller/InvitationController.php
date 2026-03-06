<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\CancelInvitation\CancelInvitationCommand;
use App\Organization\Application\Command\InviteUser\InviteUserCommand;
use App\Organization\Application\DTO\InvitationDTO;
use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Application\Query\ListInvitations\ListInvitationsQuery;
use App\Organization\Domain\ValueObject\InvitationId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Invitations')]
#[Route('/api/v1/organizations/{organizationId}/invitations', name: 'api_v1_invitations_')]
final readonly class InvitationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private CurrentUserProviderInterface $currentUserProvider,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Post(
        summary: 'Send an invitation to join organization',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'department_id', 'position_id', 'employee_number'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'department_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'position_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'employee_number', type: 'string'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Invitation sent', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'INVITATION_CREATE');
        $data = $this->decodeJson($request);

        $id = InvitationId::generate()->value();

        $this->commandBus->dispatch(new InviteUserCommand(
            id: $id,
            organizationId: $organizationId,
            email: $data['email'] ?? '',
            departmentId: $data['department_id'] ?? '',
            positionId: $data['position_id'] ?? '',
            employeeNumber: $data['employee_number'] ?? '',
            invitedByUserId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Get(summary: 'List invitations for organization')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Invitation list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: InvitationDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'INVITATION_VIEW');

        $invitations = $this->queryBus->ask(new ListInvitationsQuery(
            organizationId: $organizationId,
        ));

        return new JsonResponse($invitations);
    }

    #[OA\Post(summary: 'Cancel an invitation')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'invitationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Invitation cancelled', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{invitationId}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(string $organizationId, string $invitationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'INVITATION_DELETE');

        $this->commandBus->dispatch(new CancelInvitationCommand(
            invitationId: $invitationId,
        ));

        return new JsonResponse(['message' => 'Invitation cancelled.']);
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
