<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Application\Command\RemoveUserOverride\RemoveUserOverrideCommand;
use App\Organization\Application\Command\SetDepartmentPermissions\SetDepartmentPermissionsCommand;
use App\Organization\Application\Command\SetUserOverride\SetUserOverrideCommand;
use App\Organization\Application\DTO\DepartmentPermissionDTO;
use App\Organization\Application\DTO\EffectivePermissionDTO;
use App\Organization\Application\DTO\UserPermissionOverrideDTO;
use App\Organization\Application\Query\GetDepartmentPermissions\GetDepartmentPermissionsQuery;
use App\Organization\Application\Query\GetEffectivePermissions\GetEffectivePermissionsQuery;
use App\Organization\Application\Query\GetUserOverrides\GetUserOverridesQuery;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Permissions')]
#[Route('/api/v1/organizations/{organizationId}/permissions', name: 'api_v1_org_permissions_')]
final readonly class PermissionController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private Security $security,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    #[OA\Get(summary: 'Get department permissions')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Department permissions', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: DepartmentPermissionDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/departments/{departmentId}', name: 'dept_list', methods: ['GET'])]
    public function getDepartmentPermissions(string $organizationId, string $departmentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $result = $this->queryBus->ask(new GetDepartmentPermissionsQuery($departmentId));

        return new JsonResponse($result);
    }

    #[OA\Put(
        summary: 'Set department permissions',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permissions'],
                properties: [
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'resource', type: 'string'),
                                new OA\Property(property: 'action', type: 'string'),
                                new OA\Property(property: 'scope', type: 'string'),
                            ],
                        ),
                    ),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'departmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Department permissions updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/departments/{departmentId}', name: 'dept_set', methods: ['PUT'])]
    public function setDepartmentPermissions(
        string $organizationId,
        string $departmentId,
        Request $request,
    ): JsonResponse {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new SetDepartmentPermissionsCommand(
            departmentId: $departmentId,
            organizationId: $organizationId,
            permissions: $data['permissions'] ?? [],
            actorId: $this->getActorId(),
        ));

        return new JsonResponse(['message' => 'Department permissions updated.']);
    }

    #[OA\Get(summary: 'Get user permission overrides')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'User permission overrides', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserPermissionOverrideDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/users/{employeeId}', name: 'user_overrides_list', methods: ['GET'])]
    public function getUserOverrides(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $result = $this->queryBus->ask(new GetUserOverridesQuery($employeeId));

        return new JsonResponse($result);
    }

    #[OA\Post(
        summary: 'Set user permission override',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['resource', 'action', 'effect', 'scope'],
                properties: [
                    new OA\Property(property: 'resource', type: 'string'),
                    new OA\Property(property: 'action', type: 'string'),
                    new OA\Property(property: 'effect', type: 'string', enum: ['allow', 'deny']),
                    new OA\Property(property: 'scope', type: 'string'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Override created', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/users/{employeeId}', name: 'user_overrides_set', methods: ['POST'])]
    public function setUserOverride(
        string $organizationId,
        string $employeeId,
        Request $request,
    ): JsonResponse {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new SetUserOverrideCommand(
            employeeId: $employeeId,
            organizationId: $organizationId,
            resource: $data['resource'] ?? '',
            action: $data['action'] ?? '',
            effect: $data['effect'] ?? '',
            scope: $data['scope'] ?? '',
            actorId: $this->getActorId(),
        ));

        return new JsonResponse(['message' => 'User override set.'], Response::HTTP_CREATED);
    }

    #[OA\Delete(summary: 'Remove user permission override')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'overrideId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Override removed')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/users/{employeeId}/{overrideId}', name: 'user_overrides_remove', methods: ['DELETE'])]
    public function removeUserOverride(
        string $organizationId,
        string $employeeId,
        string $overrideId,
    ): JsonResponse {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');

        $this->commandBus->dispatch(new RemoveUserOverrideCommand(
            overrideId: $overrideId,
            organizationId: $organizationId,
            actorId: $this->getActorId(),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(summary: 'Get effective permissions for employee')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Effective permissions', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: EffectivePermissionDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Employee not found')]
    #[Route('/users/{employeeId}/effective', name: 'user_effective', methods: ['GET'])]
    public function getEffectivePermissions(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $employee = $this->employeeRepository->findById(EmployeeId::fromString($employeeId));

        if (null === $employee) {
            return new JsonResponse(['error' => 'Employee not found.'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->queryBus->ask(new GetEffectivePermissionsQuery(
            userId: $employee->userId(),
            organizationId: $organizationId,
        ));

        return new JsonResponse($result);
    }

    private function getActorId(): string
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        return $user->getId();
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
