<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetStartFormSchema;

use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetStartFormSchemaHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
    ) {
    }

    /**
     * @return array{fields: list<array<string, mixed>>}
     */
    public function __invoke(GetStartFormSchemaQuery $query): array
    {
        $defId = ProcessDefinitionId::fromString($query->processDefinitionId);
        $definition = $this->definitionRepository->findById($defId);

        if (null === $definition || !$definition->isPublished()) {
            return ['fields' => []];
        }

        $version = $this->versionRepository->findLatestByProcessDefinitionId($defId);
        if (null === $version) {
            return ['fields' => []];
        }

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $startNodeId = $graph->startNodeId();
        $startConfig = $graph->nodeConfig($startNodeId);

        $formFields = $startConfig['formFields'] ?? [];

        return ['fields' => \is_array($formFields) ? $formFields : []];
    }
}
