<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\StartProcess;

use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\Exception\ProcessDefinitionNotFoundException;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class StartProcessHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private WorkflowEngine $engine,
    ) {
    }

    public function __invoke(StartProcessCommand $command): void
    {
        $defId = ProcessDefinitionId::fromString($command->processDefinitionId);
        $definition = $this->definitionRepository->findById($defId);

        if (null === $definition) {
            throw ProcessDefinitionNotFoundException::withId($command->processDefinitionId);
        }

        if (!$definition->isPublished()) {
            throw WorkflowExecutionException::invalidTransition('Process definition is not published');
        }

        $version = $this->versionRepository->findLatestByProcessDefinitionId($defId);
        if (null === $version) {
            throw WorkflowExecutionException::invalidTransition('No published version found');
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $startNodeId = NodeId::fromString($graph->startNodeId());

        $instanceId = ProcessInstanceId::fromString($command->id);
        $variables = $command->variables;
        $variables['_task_creator_id'] = $command->startedBy;

        $instance = ProcessInstance::start(
            id: $instanceId,
            processDefinitionId: $defId,
            versionId: $version->id(),
            organizationId: $command->organizationId,
            startedBy: $command->startedBy,
            variables: $variables,
            startNodeId: $startNodeId,
        );

        $activeTokens = $instance->activeTokens();
        $this->engine->advanceToken($instance, $activeTokens[0]->id(), $graph);

        $this->instanceRepository->save($instance);
    }
}
