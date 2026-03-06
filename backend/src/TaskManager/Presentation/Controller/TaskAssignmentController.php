<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Command\AddAssignment\AddAssignmentCommand;
use App\TaskManager\Application\Command\RemoveAssignment\RemoveAssignmentCommand;
use App\TaskManager\Application\DTO\TaskAssignmentDTO;
use App\TaskManager\Application\Query\GetTaskAssignments\GetTaskAssignmentsQuery;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(name: 'Tasks')]
#[Route('/api/v1/organizations/{organizationId}/tasks/{taskId}/assignments', name: 'api_v1_assignments_')]
final readonly class TaskAssignmentController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[OA\Get(summary: 'List task assignments')]
    #[OA\Response(response: 200, description: 'Assignment list', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: TaskAssignmentDTO::class))))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $assignments = $this->queryBus->ask(new GetTaskAssignmentsQuery($taskId));

        return new JsonResponse($assignments);
    }

    #[OA\Post(
        summary: 'Add assignment to task',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['employee_id'],
                properties: [
                    new OA\Property(property: 'employee_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'role', type: 'string', enum: ['assignee', 'watcher']),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 201, description: 'Assignment created', content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR) ?? [];

        $id = Uuid::v7()->toRfc4122();

        $this->commandBus->dispatch(new AddAssignmentCommand(
            id: $id,
            taskId: $taskId,
            employeeId: $data['employee_id'] ?? '',
            role: $data['role'] ?? 'assignee',
            assignedBy: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[OA\Delete(summary: 'Remove assignment from task')]
    #[OA\Parameter(name: 'assignmentId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Assignment removed', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/{assignmentId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $taskId, string $assignmentId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');

        $this->commandBus->dispatch(new RemoveAssignmentCommand($assignmentId));

        return new JsonResponse(['message' => 'Assignment removed.']);
    }
}
