<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Controller;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Organization\Application\Command\RemoveUserOverride\RemoveUserOverrideCommand;
use App\Organization\Application\Command\SetDepartmentPermissions\SetDepartmentPermissionsCommand;
use App\Organization\Application\Command\SetUserOverride\SetUserOverrideCommand;
use App\Organization\Application\Query\GetDepartmentPermissions\GetDepartmentPermissionsQuery;
use App\Organization\Application\Query\GetEffectivePermissions\GetEffectivePermissionsQuery;
use App\Organization\Application\Query\GetUserOverrides\GetUserOverridesQuery;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/departments/{departmentId}', name: 'dept_list', methods: ['GET'])]
    public function getDepartmentPermissions(string $organizationId, string $departmentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $result = $this->queryBus->ask(new GetDepartmentPermissionsQuery($departmentId));

        return new JsonResponse($result);
    }

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

    #[Route('/users/{employeeId}', name: 'user_overrides_list', methods: ['GET'])]
    public function getUserOverrides(string $organizationId, string $employeeId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'ROLE_VIEW');

        $result = $this->queryBus->ask(new GetUserOverridesQuery($employeeId));

        return new JsonResponse($result);
    }

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
        /** @var array<string, mixed> */
        return json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];
    }
}
