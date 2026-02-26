<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Timer;

use App\Workflow\Domain\Exception\ProcessInstanceNotFoundException;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\TokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FireTimerHandler
{
    public function __construct(
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private WorkflowEngine $engine,
    ) {
    }

    public function __invoke(FireTimerMessage $message): void
    {
        $instanceId = ProcessInstanceId::fromString($message->processInstanceId);
        $instance = $this->instanceRepository->findById($instanceId);

        if (null === $instance) {
            throw ProcessInstanceNotFoundException::withId($message->processInstanceId);
        }

        if (!$instance->isRunning()) {
            return;
        }

        $version = $this->versionRepository->findById($instance->versionId());
        if (null === $version) {
            throw WorkflowExecutionException::invalidTransition('Version not found');
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $tokenId = TokenId::fromString($message->tokenId);
        $nodeId = NodeId::fromString($message->nodeId);

        $instance->fireTimer($nodeId, $tokenId);
        $this->engine->resumeToken($instance, $tokenId, $graph);

        $this->instanceRepository->save($instance);
    }
}
