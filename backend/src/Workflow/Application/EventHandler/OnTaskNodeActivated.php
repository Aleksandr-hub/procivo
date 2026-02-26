<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Shared\Application\Bus\CommandBusInterface;
use App\TaskManager\Application\Command\CreateTask\CreateTaskCommand;
use App\Workflow\Domain\Entity\WorkflowTaskLink;
use App\Workflow\Domain\Event\TaskNodeActivatedEvent;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskNodeActivated
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private WorkflowTaskLinkRepositoryInterface $linkRepository,
    ) {
    }

    public function __invoke(TaskNodeActivatedEvent $event): void
    {
        $taskConfig = $event->taskConfig;
        $taskId = Uuid::v4()->toRfc4122();

        $title = (string) ($taskConfig['task_title_template'] ?? $event->nodeName);
        $description = isset($taskConfig['task_description_template']) ? (string) $taskConfig['task_description_template'] : null;
        $assigneeId = isset($taskConfig['assignee_value']) ? (string) $taskConfig['assignee_value'] : null;
        $priority = (string) ($taskConfig['priority'] ?? 'medium');

        $this->commandBus->dispatch(new CreateTaskCommand(
            id: $taskId,
            organizationId: $event->organizationId,
            title: $title,
            description: $description,
            priority: $priority,
            dueDate: null,
            estimatedHours: null,
            creatorId: 'system',
            assigneeId: $assigneeId,
        ));

        $link = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: $event->processInstanceId,
            tokenId: $event->tokenId,
            taskId: $taskId,
        );

        $this->linkRepository->save($link);
    }
}
