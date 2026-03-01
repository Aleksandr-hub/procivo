<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Command\AssignTask\AssignTaskCommand;
use App\TaskManager\Application\Command\ClaimTask\ClaimTaskCommand;
use App\TaskManager\Application\Command\CreateTask\CreateTaskCommand;
use App\TaskManager\Application\Command\DeleteTask\DeleteTaskCommand;
use App\TaskManager\Application\Command\TransitionTask\TransitionTaskCommand;
use App\TaskManager\Application\Command\UnclaimTask\UnclaimTaskCommand;
use App\TaskManager\Application\Command\UpdateTask\UpdateTaskCommand;
use App\TaskManager\Application\DTO\TaskDTO;
use App\TaskManager\Application\Query\GetTask\GetTaskQuery;
use App\TaskManager\Application\Query\ListTasks\ListTasksQuery;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\Workflow\Application\Command\ExecuteTaskAction\ExecuteTaskActionCommand;
use App\Workflow\Application\DTO\TaskWorkflowSummaryDTO;
use App\Workflow\Application\Query\BatchTaskWorkflowSummary\BatchTaskWorkflowSummaryQuery;
use App\Workflow\Application\Query\GetTaskWorkflowContext\GetTaskWorkflowContextQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/tasks', name: 'api_v1_tasks_')]
final readonly class TaskController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_CREATE');
        $data = $this->decodeJson($request);

        $id = TaskId::generate()->value();

        $this->commandBus->dispatch(new CreateTaskCommand(
            id: $id,
            organizationId: $organizationId,
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            priority: $data['priority'] ?? 'medium',
            dueDate: $data['due_date'] ?? null,
            estimatedHours: isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : null,
            creatorId: $data['creator_id'] ?? '',
            assigneeId: isset($data['assignee_id']) && \is_string($data['assignee_id']) ? $data['assignee_id'] : null,
            assignmentStrategy: isset($data['assignment_strategy']) && \is_string($data['assignment_strategy']) ? $data['assignment_strategy'] : 'unassigned',
            assigneeEmployeeId: isset($data['assignee_employee_id']) && \is_string($data['assignee_employee_id']) ? $data['assignee_employee_id'] : null,
            assigneeRoleId: isset($data['assignee_role_id']) && \is_string($data['assignee_role_id']) ? $data['assignee_role_id'] : null,
            assigneeDepartmentId: isset($data['assignee_department_id']) && \is_string($data['assignee_department_id']) ? $data['assignee_department_id'] : null,
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $status = $request->query->get('status');
        $assigneeId = $request->query->get('assignee_id');
        $candidateEmployeeId = $request->query->get('candidate_employee_id');

        /** @var list<TaskDTO> $tasks */
        $tasks = $this->queryBus->ask(new ListTasksQuery(
            organizationId: $organizationId,
            status: \is_string($status) ? $status : null,
            assigneeId: \is_string($assigneeId) ? $assigneeId : null,
            candidateEmployeeId: \is_string($candidateEmployeeId) ? $candidateEmployeeId : null,
        ));

        $taskIds = array_map(static fn (TaskDTO $t) => $t->id, $tasks);

        /** @var array<string, TaskWorkflowSummaryDTO> $summaries */
        $summaries = $this->queryBus->ask(new BatchTaskWorkflowSummaryQuery($taskIds));

        $result = array_map(static function (TaskDTO $t) use ($summaries): array {
            /** @var array<string, mixed> $data */
            $data = json_decode(json_encode($t, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
            $data['workflow_summary'] = $summaries[$t->id] ?? null;

            return $data;
        }, $tasks);

        return new JsonResponse($result);
    }

    #[Route('/{taskId}', name: 'show', methods: ['GET'])]
    public function show(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');

        $dto = $this->queryBus->ask(new GetTaskQuery($taskId));
        $workflowContext = $this->queryBus->ask(new GetTaskWorkflowContextQuery($taskId));

        /** @var array<string, mixed> $taskData */
        $taskData = json_decode(json_encode($dto, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);

        if (null !== $workflowContext) {
            /** @var array<string, mixed> $contextData */
            $contextData = json_decode(json_encode($workflowContext, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
            if (null !== $dto->formSchema) {
                $contextData['form_schema'] = $dto->formSchema;
            }
            $taskData['workflow_context'] = $contextData;
        } else {
            $taskData['workflow_context'] = null;
        }

        return new JsonResponse($taskData);
    }

    #[Route('/{taskId}/complete', name: 'complete', methods: ['POST'])]
    public function complete(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        /** @var string $actionKey */
        $actionKey = $data['action_key'] ?? '';
        /** @var array<string, mixed> $formData */
        $formData = isset($data['form_data']) && \is_array($data['form_data']) ? $data['form_data'] : [];

        $this->commandBus->dispatch(new ExecuteTaskActionCommand(
            taskId: $taskId,
            actionKey: $actionKey,
            formData: $formData,
        ));

        return new JsonResponse(['message' => 'Task completed.']);
    }

    #[Route('/{taskId}', name: 'update', methods: ['PUT'])]
    public function update(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UpdateTaskCommand(
            taskId: $taskId,
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            priority: $data['priority'] ?? 'medium',
            dueDate: $data['due_date'] ?? null,
            estimatedHours: isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : null,
        ));

        return new JsonResponse(['message' => 'Task updated.']);
    }

    #[Route('/{taskId}/transition', name: 'transition', methods: ['POST'])]
    public function transition(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new TransitionTaskCommand(
            taskId: $taskId,
            transition: $data['transition'] ?? '',
            actorId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Task status updated.']);
    }

    #[Route('/{taskId}/assign', name: 'assign', methods: ['PUT'])]
    public function assign(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new AssignTaskCommand(
            taskId: $taskId,
            assigneeId: isset($data['assignee_id']) && \is_string($data['assignee_id']) ? $data['assignee_id'] : null,
            actorId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Task assignee updated.']);
    }

    #[Route('/{taskId}/claim', name: 'claim', methods: ['POST'])]
    public function claim(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new ClaimTaskCommand(
            taskId: $taskId,
            employeeId: isset($data['employee_id']) && \is_string($data['employee_id']) ? $data['employee_id'] : '',
            actorId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Task claimed.']);
    }

    #[Route('/{taskId}/unclaim', name: 'unclaim', methods: ['POST'])]
    public function unclaim(string $organizationId, string $taskId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_UPDATE');
        $data = $this->decodeJson($request);

        $this->commandBus->dispatch(new UnclaimTaskCommand(
            taskId: $taskId,
            employeeId: isset($data['employee_id']) && \is_string($data['employee_id']) ? $data['employee_id'] : '',
            actorId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(['message' => 'Task returned to queue.']);
    }

    #[Route('/{taskId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $organizationId, string $taskId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_DELETE');

        $this->commandBus->dispatch(new DeleteTaskCommand(
            taskId: $taskId,
            actorId: $this->currentUserProvider->getUserId(),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
