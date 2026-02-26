<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Shared\Application\Bus\CommandBusInterface;
use App\TaskManager\Domain\Event\TaskStatusChangedEvent;
use App\Workflow\Application\Command\CompleteTaskNode\CompleteTaskNodeCommand;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnWorkflowTaskCompleted
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private WorkflowTaskLinkRepositoryInterface $linkRepository,
    ) {
    }

    public function __invoke(TaskStatusChangedEvent $event): void
    {
        if ('done' !== $event->newStatus) {
            return;
        }

        $link = $this->linkRepository->findByTaskId($event->taskId);
        if (null === $link || $link->isCompleted()) {
            return;
        }

        $this->commandBus->dispatch(new CompleteTaskNodeCommand(
            processInstanceId: $link->processInstanceId(),
            tokenId: $link->tokenId(),
        ));
    }
}
