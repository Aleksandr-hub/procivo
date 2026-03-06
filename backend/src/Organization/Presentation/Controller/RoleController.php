<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Application\Command\AssignRole\AssignRoleCommand;
use App\Organization\Application\Command\CreateRole\CreateRoleCommand;
use App\Organization\Application\Command\DeleteRole\DeleteRoleCommand;
use App\Organization\Application\Command\GrantPermission\GrantPermissionCommand;
use App\Organization\Application\Command\RevokePermission\RevokePermissionCommand;
use App\Organization\Application\Command\RevokeRole\RevokeRoleCommand;
use App\Organization\Application\Command\UpdateRole\UpdateRoleCommand;
use App\Organization\Application\DTO\PermissionDTO;
use App\Organization\Application\DTO\RoleDTO;
use App\Organization\Application\Query\GetEmployeeRoles\GetEmployeeRolesQuery;
use App\Organization\Application\Query\GetMyPermissions\GetMyPermissionsQuery;
use App\Organization\Application\Query\GetRole\GetRoleQuery;
use App\Organization\Application\Query\ListRoles\ListRolesQuery;
use App\Organization\Domain\ValueObject\PermissionId;
use App\Organization\Domain\ValueObject\RoleId;
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

#[OA\Tag(name: 'Roles')]
#[Route('/api/v1/organizations/{organizationId}', name: 'api_v1_org_')]
final readonly class RoleController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private Security $security,
    ) {
    }

    #[OA\Get(summary: 'List roles in organization')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Role list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: RoleDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles', name: 'roles_list', methods: ['GET'])]
    public function listRoles(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $roles = $this->queryBus->ask(new ListRolesQuery($organizationId));

        return new JsonResponse($roles);
    }

    #[OA\Get(summary: 'Get role by ID')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Role details', content: new OA\JsonContent(ref: new Model(type: RoleDTO::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles/{roleId}', name: 'roles_show', methods: ['GET'])]
    public function showRole(string $organizationId, string $roleId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $role = $this->queryBus->ask(new GetRoleQuery($roleId));

        return new JsonResponse($role);
    }

    #[OA\Post(
        summary: 'Create a role',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'hierarchy', type: 'integer', default: 100),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Role created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles', name: 'roles_create', methods: ['POST'])]
    public function createRole(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_CREATE');
        $data = $this->decodeJson($request);

        $id = RoleId::generate()->value();

        $this->commandBus->dispatch(new CreateRoleCommand(
            id: $id,
            organizationId: $organizationId,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            hierarchy: (int) ($data['hierarchy'] ?? 100),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Put(
        summary: 'Update role',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'hierarchy', type: 'integer', default: 100),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Role updated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles/{roleId}', name: 'roles_update', methods: ['PUT'])]
    public function updateRole(string $organizationId, string $roleId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateRoleCommand(
            roleId: $roleId,
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            hierarchy: (int) ($data['hierarchy'] ?? 100),
        ));

        return new JsonResponse(['message' => 'Role updated.']);
    }

    #[OA\Delete(summary: 'Delete role')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Role deleted')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles/{roleId}', name: 'roles_delete', methods: ['DELETE'])]
    public function deleteRole(string $organizationId, string $roleId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_DELETE');

        $this->commandBus->dispatch(new DeleteRoleCommand($roleId));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(
        summary: 'Grant permission to role',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['resource', 'action', 'scope'],
                properties: [
                    new OA\Property(property: 'resource', type: 'string'),
                    new OA\Property(property: 'action', type: 'string'),
                    new OA\Property(property: 'scope', type: 'string'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 201, description: 'Permission granted', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles/{roleId}/permissions', name: 'roles_grant_permission', methods: ['POST'])]
    public function grantPermission(string $organizationId, string $roleId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');
        $data = $this->decodeJson($request);

        $id = PermissionId::generate()->value();

        $this->commandBus->dispatch(new GrantPermissionCommand(
            id: $id,
            roleId: $roleId,
            organizationId: $organizationId,
            resource: $data['resource'] ?? '',
            action: $data['action'] ?? '',
            scope: $data['scope'] ?? '',
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Delete(summary: 'Revoke permission from role')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'permissionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Permission revoked')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/roles/{roleId}/permissions/{permissionId}', name: 'roles_revoke_permission', methods: ['DELETE'])]
    public function revokePermission(string $organizationId, string $roleId, string $permissionId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');

        $this->commandBus->dispatch(new RevokePermissionCommand($permissionId));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(summary: 'Get roles assigned to employee')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Employee roles', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: RoleDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/employees/{employeeId}/roles', name: 'employee_roles_list', methods: ['GET'])]
    public function employeeRoles(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $roles = $this->queryBus->ask(new GetEmployeeRolesQuery($employeeId));

        return new JsonResponse($roles);
    }

    #[OA\Post(
        summary: 'Assign role to employee',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role_id'],
                properties: [
                    new OA\Property(property: 'role_id', type: 'string', format: 'uuid'),
                ],
            ),
        ),
    )]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Role assigned', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/employees/{employeeId}/roles', name: 'employee_roles_assign', methods: ['POST'])]
    public function assignRole(string $organizationId, string $employeeId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new AssignRoleCommand(
            employeeId: $employeeId,
            roleId: $data['role_id'] ?? '',
            organizationId: $organizationId,
        ));

        return new JsonResponse(['message' => 'Role assigned.']);
    }

    #[OA\Delete(summary: 'Revoke role from employee')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Parameter(name: 'roleId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 204, description: 'Role revoked')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/employees/{employeeId}/roles/{roleId}', name: 'employee_roles_revoke', methods: ['DELETE'])]
    public function revokeRole(string $organizationId, string $employeeId, string $roleId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_UPDATE');

        $this->commandBus->dispatch(new RevokeRoleCommand(
            employeeId: $employeeId,
            roleId: $roleId,
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(summary: 'Get current user effective permissions in organization')]
    #[OA\Parameter(name: 'organizationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Effective permissions', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: PermissionDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/my-permissions', name: 'my_permissions', methods: ['GET'])]
    public function myPermissions(string $organizationId): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $result = $this->queryBus->ask(new GetMyPermissionsQuery(
            userId: $user->getId(),
            organizationId: $organizationId,
        ));

        return new JsonResponse($result);
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
