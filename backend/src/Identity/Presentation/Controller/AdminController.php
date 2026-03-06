<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\EndImpersonation\EndImpersonationCommand;
use App\Identity\Application\DTO\ImpersonationDTO;
use App\Identity\Application\Query\ImpersonateUser\ImpersonateUserQuery;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Admin')]
#[Route('/api/v1/admin', name: 'api_v1_admin_')]
final readonly class AdminController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[OA\Post(summary: 'End impersonation session')]
    #[OA\Response(response: 204, description: 'Impersonation ended')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Super admin access required')]
    #[Route('/impersonate/end', name: 'end_impersonate', methods: ['POST'])]
    public function endImpersonation(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $this->assertSuperAdmin($user);

        $this->commandBus->dispatch(new EndImpersonationCommand(
            adminUserId: $user->getId(),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(
        summary: 'Impersonate a user (super admin only)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', description: 'Reason for impersonation'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'userId', in: 'path', required: true, description: 'Target user UUID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Impersonation session created', content: new OA\JsonContent(ref: new Model(type: ImpersonationDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Super admin access required or chained impersonation detected')]
    #[Route('/impersonate/{userId}', name: 'impersonate', methods: ['POST'])]
    public function impersonate(
        string $userId,
        Request $request,
        #[CurrentUser] SecurityUser $user,
    ): JsonResponse {
        $this->assertSuperAdmin($user);

        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
        $reason = (string) ($data['reason'] ?? '');

        // Detect if current JWT already has impersonated_by claim
        $isAlreadyImpersonating = $this->detectImpersonation($request);

        $dto = $this->queryBus->ask(new ImpersonateUserQuery(
            adminUserId: $user->getId(),
            targetUserId: $userId,
            reason: $reason,
            isAlreadyImpersonating: $isAlreadyImpersonating,
        ));

        return new JsonResponse($dto);
    }

    private function assertSuperAdmin(SecurityUser $user): void
    {
        if (!\in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('Super admin access required.');
        }
    }

    private function detectImpersonation(Request $request): bool
    {
        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        $token = substr($authHeader, 7);
        $parts = explode('.', $token);

        if (3 !== \count($parts)) {
            return false;
        }

        $payload = json_decode(base64_decode($parts[1], true) ?: '', true);

        return \is_array($payload) && isset($payload['impersonated_by']);
    }
}
