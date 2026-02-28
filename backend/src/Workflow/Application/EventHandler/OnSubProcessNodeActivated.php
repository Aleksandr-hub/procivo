<?php

declare(strict_types=1);

namespace App\Workflow\Application\EventHandler;

use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\Event\SubProcessNodeActivatedEvent;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnSubProcessNodeActivated
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private WorkflowEngine $engine,
    ) {
    }

    public function __invoke(SubProcessNodeActivatedEvent $event): void
    {
        $config = $event->subProcessConfig;
        $subProcessDefinitionId = (string) ($config['sub_process_definition_id'] ?? '');

        if ('' === $subProcessDefinitionId) {
            return;
        }

        $defId = ProcessDefinitionId::fromString($subProcessDefinitionId);
        $definition = $this->definitionRepository->findById($defId);

        if (null === $definition || !$definition->isPublished()) {
            return;
        }

        $version = $this->versionRepository->findLatestByProcessDefinitionId($defId);
        if (null === $version) {
            return;
        }

        $childVariables = $this->mapVariables($config, $event->variables);

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $startNodeId = NodeId::fromString($graph->startNodeId());

        $childInstanceId = ProcessInstanceId::generate();
        $childInstance = ProcessInstance::startAsSubProcess(
            id: $childInstanceId,
            processDefinitionId: $defId,
            versionId: $version->id(),
            organizationId: $event->organizationId,
            startedBy: $event->startedBy,
            variables: $childVariables,
            startNodeId: $startNodeId,
            parentProcessInstanceId: $event->processInstanceId,
            parentTokenId: $event->tokenId,
        );

        $activeTokens = $childInstance->activeTokens();
        $this->engine->advanceToken($childInstance, $activeTokens[0]->id(), $graph);

        $this->instanceRepository->save($childInstance);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $parentVariables
     *
     * @return array<string, mixed>
     */
    private function mapVariables(array $config, array $parentVariables): array
    {
        /** @var list<array{parent_var: string, child_var: string}> $mappings */
        $mappings = $config['variable_mappings'] ?? [];

        if ([] === $mappings) {
            return $parentVariables;
        }

        $childVariables = [];
        foreach ($mappings as $mapping) {
            $parentKey = $mapping['parent_var'];
            $childKey = $mapping['child_var'];
            if (isset($parentVariables[$parentKey])) {
                $childVariables[$childKey] = $parentVariables[$parentKey];
            }
        }

        return $childVariables;
    }
}
