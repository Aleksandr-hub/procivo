<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\CompleteTaskNode;

use App\Workflow\Domain\Exception\ProcessInstanceNotFoundException;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\TokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CompleteTaskNodeHandler
{
    public function __construct(
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private WorkflowEngine $engine,
    ) {
    }

    public function __invoke(CompleteTaskNodeCommand $command): void
    {
        $instanceId = ProcessInstanceId::fromString($command->processInstanceId);
        $instance = $this->instanceRepository->findById($instanceId);

        if (null === $instance) {
            throw ProcessInstanceNotFoundException::withId($command->processInstanceId);
        }

        if (!$instance->isRunning()) {
            throw WorkflowExecutionException::processNotRunning($command->processInstanceId);
        }

        $version = $this->versionRepository->findById($instance->versionId());
        if (null === $version) {
            throw WorkflowExecutionException::invalidTransition('Version not found');
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $tokenId = TokenId::fromString($command->tokenId);

        $this->engine->resumeToken($instance, $tokenId, $graph);

        $this->instanceRepository->save($instance);
    }
}
