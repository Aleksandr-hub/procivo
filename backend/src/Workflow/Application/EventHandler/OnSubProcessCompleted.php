<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Workflow\Domain\Event\ProcessCompletedEvent;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use App\Workflow\Domain\ValueObject\TokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnSubProcessCompleted
{
    public function __construct(
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private WorkflowEngine $engine,
    ) {
    }

    public function __invoke(ProcessCompletedEvent $event): void
    {
        $childInstance = $this->instanceRepository->findById(
            ProcessInstanceId::fromString($event->processInstanceId),
        );

        if (null === $childInstance) {
            return;
        }

        $parentProcessInstanceId = $childInstance->parentProcessInstanceId();
        $parentTokenId = $childInstance->parentTokenId();

        if (null === $parentProcessInstanceId || null === $parentTokenId) {
            return;
        }

        $parentInstance = $this->instanceRepository->findById(
            ProcessInstanceId::fromString($parentProcessInstanceId),
        );

        if (null === $parentInstance || !$parentInstance->isRunning()) {
            return;
        }

        $tokenId = TokenId::fromString($parentTokenId);
        $token = $parentInstance->getToken($tokenId);
        $nodeId = $token->nodeId();

        $parentInstance->completeSubProcess($nodeId, $tokenId, $event->processInstanceId);

        $version = $this->versionRepository->findById($parentInstance->versionId());
        if (null === $version) {
            return;
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $this->engine->resumeToken($parentInstance, $tokenId, $graph);
        $this->instanceRepository->save($parentInstance);
    }
}
