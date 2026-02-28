<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Shared\Application\Bus\CommandBusInterface;
use App\TaskManager\Application\Command\CreateTask\CreateTaskCommand;
use App\TaskManager\Application\Command\TransitionTask\TransitionTaskCommand;
use App\TaskManager\Application\Command\UpdateTask\UpdateTaskCommand;
use App\Workflow\Application\Service\FormSchemaBuilder;
use App\Workflow\Domain\Entity\WorkflowTaskLink;
use App\Workflow\Domain\Event\TaskNodeActivatedEvent;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskNodeActivated
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private WorkflowTaskLinkRepositoryInterface $linkRepository,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private FormSchemaBuilder $formSchemaBuilder,
    ) {
    }

    public function __invoke(TaskNodeActivatedEvent $event): void
    {
        $taskConfig = $event->taskConfig;

        $instance = $this->instanceRepository->findById(
            ProcessInstanceId::fromString($event->processInstanceId),
        );
        $variables = $instance?->variables() ?? [];

        // Fallback chain: taskConfig → process variables → default
        $title = (string) ($taskConfig['task_title_template']
            ?? $variables['_task_title']
            ?? $event->nodeName);

        $description = isset($taskConfig['task_description_template'])
            ? (string) $taskConfig['task_description_template']
            : (isset($variables['_task_description']) ? (string) $variables['_task_description'] : null);

        $priority = (string) ($taskConfig['priority']
            ?? $variables['_task_priority']
            ?? 'medium');

        // Assignment strategy from config (new) or fallback to legacy assignee_value
        $assignmentStrategy = isset($taskConfig['assignment_strategy'])
            ? (string) $taskConfig['assignment_strategy']
            : 'unassigned';
        $assigneeEmployeeId = isset($taskConfig['assignee_employee_id'])
            ? (string) $taskConfig['assignee_employee_id']
            : null;
        $assigneeRoleId = isset($taskConfig['assignee_role_id'])
            ? (string) $taskConfig['assignee_role_id']
            : null;
        $assigneeDepartmentId = isset($taskConfig['assignee_department_id'])
            ? (string) $taskConfig['assignee_department_id']
            : null;

        // Resolve from_variable strategy: look up convention variable _assignee_for_{nodeId}
        if ('from_variable' === $assignmentStrategy) {
            $variableName = '_assignee_for_' . $event->nodeId;
            $resolvedAssignee = isset($variables[$variableName]) && '' !== (string) $variables[$variableName]
                ? (string) $variables[$variableName]
                : null;

            if (null !== $resolvedAssignee) {
                $assignmentStrategy = 'specific_user';
                $assigneeEmployeeId = $resolvedAssignee;
            } else {
                $assignmentStrategy = 'unassigned';
            }
        }

        // Legacy fallback: if old assignee_value exists and no new strategy set
        $legacyAssigneeId = null;
        if ('unassigned' === $assignmentStrategy && isset($taskConfig['assignee_value'])) {
            $legacyAssigneeId = (string) $taskConfig['assignee_value'];
        }

        if (null === $legacyAssigneeId && isset($variables['_task_assignee_id'])) {
            $legacyAssigneeId = (string) $variables['_task_assignee_id'];
        }

        // Build form schema from process graph
        $formSchema = null;
        if (null !== $instance) {
            $version = $this->versionRepository->findById($instance->versionId());
            if (null !== $version) {
                $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
                $formSchema = $this->formSchemaBuilder->build($graph, $event->nodeId);
            }
        }

        $dueDate = isset($variables['_task_due_date']) ? (string) $variables['_task_due_date'] : null;

        $creatorId = isset($variables['_task_creator_id']) ? (string) $variables['_task_creator_id'] : 'system';

        $existingLink = $this->linkRepository->findLatestByProcessInstanceId($event->processInstanceId);

        if (null !== $existingLink) {
            $taskId = $existingLink->taskId();

            $this->commandBus->dispatch(new UpdateTaskCommand(
                taskId: $taskId,
                title: $title,
                description: $description,
                priority: $priority,
                dueDate: $dueDate,
                estimatedHours: null,
            ));
        } else {
            $taskId = Uuid::v4()->toRfc4122();

            $this->commandBus->dispatch(new CreateTaskCommand(
                id: $taskId,
                organizationId: $event->organizationId,
                title: $title,
                description: $description,
                priority: $priority,
                dueDate: $dueDate,
                estimatedHours: null,
                creatorId: $creatorId,
                assigneeId: $legacyAssigneeId,
                assignmentStrategy: $assignmentStrategy,
                assigneeEmployeeId: $assigneeEmployeeId,
                assigneeRoleId: $assigneeRoleId,
                assigneeDepartmentId: $assigneeDepartmentId,
                formSchema: $formSchema,
            ));

            $this->commandBus->dispatch(new TransitionTaskCommand(
                taskId: $taskId,
                transition: 'open',
            ));
        }

        $link = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: $event->processInstanceId,
            tokenId: $event->tokenId,
            taskId: $taskId,
            nodeName: $event->nodeName,
        );

        $this->linkRepository->save($link);
    }
}
