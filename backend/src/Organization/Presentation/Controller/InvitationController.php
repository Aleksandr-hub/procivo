<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Organization\Application\Command\CancelInvitation\CancelInvitationCommand;
use App\Organization\Application\Command\InviteUser\InviteUserCommand;
use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Application\Query\ListInvitations\ListInvitationsQuery;
use App\Organization\Domain\ValueObject\InvitationId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'INVITATION_VIEW');

        $invitations = $this->queryBus->ask(new ListInvitationsQuery(
            organizationId: $organizationId,
        ));

        return new JsonResponse($invitations);
    }

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
        /** @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
