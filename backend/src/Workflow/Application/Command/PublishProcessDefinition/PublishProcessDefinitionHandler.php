<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\PublishProcessDefinition;

use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\Exception\InvalidProcessGraphException;
use App\Workflow\Domain\Exception\ProcessDefinitionNotFoundException;
use App\Workflow\Domain\Repository\NodeRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\TransitionRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraphValidator;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class PublishProcessDefinitionHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private NodeRepositoryInterface $nodeRepository,
        private TransitionRepositoryInterface $transitionRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private ProcessGraphValidator $graphValidator,
    ) {
    }

    public function __invoke(PublishProcessDefinitionCommand $command): void
    {
        $defId = ProcessDefinitionId::fromString($command->processDefinitionId);
        $definition = $this->definitionRepository->findById($defId);

        if (null === $definition) {
            throw ProcessDefinitionNotFoundException::withId($command->processDefinitionId);
        }

        $nodes = $this->nodeRepository->findByProcessDefinitionId($defId);
        $transitions = $this->transitionRepository->findByProcessDefinitionId($defId);

        // Validate graph
        $result = $this->graphValidator->validate($nodes, $transitions);
        if (!$result->isValid()) {
            throw InvalidProcessGraphException::withErrors($result->errors());
        }

        // Determine version number
        $latestVersion = $this->versionRepository->findLatestByProcessDefinitionId($defId);
        $versionNumber = null !== $latestVersion ? $latestVersion->versionNumber() + 1 : 1;

        // Create snapshot
        $snapshot = [
            'nodes' => array_map(static fn ($n) => $n->toSnapshot(), $nodes),
            'transitions' => array_map(static fn ($t) => $t->toSnapshot(), $transitions),
        ];

        $versionId = ProcessDefinitionVersionId::generate();
        $version = ProcessDefinitionVersion::create(
            id: $versionId,
            processDefinitionId: $defId,
            versionNumber: $versionNumber,
            nodesSnapshot: $snapshot,
            publishedBy: $command->publishedBy,
        );

        $this->versionRepository->save($version);

        // Update definition status
        $definition->publish($versionId->value(), $versionNumber, $command->publishedBy);
        $this->definitionRepository->save($definition);
    }
}
