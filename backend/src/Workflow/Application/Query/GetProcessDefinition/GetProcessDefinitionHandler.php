<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\GetProcessDefinition;

use App\Workflow\Application\DTO\NodeDTO;
use App\Workflow\Application\DTO\ProcessDefinitionDetailDTO;
use App\Workflow\Application\DTO\ProcessDefinitionDTO;
use App\Workflow\Application\DTO\TransitionDTO;
use App\Workflow\Domain\Exception\ProcessDefinitionNotFoundException;
use App\Workflow\Domain\Repository\NodeRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\Repository\TransitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProcessDefinitionHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private NodeRepositoryInterface $nodeRepository,
        private TransitionRepositoryInterface $transitionRepository,
    ) {
    }

    public function __invoke(GetProcessDefinitionQuery $query): ProcessDefinitionDetailDTO
    {
        $defId = ProcessDefinitionId::fromString($query->processDefinitionId);
        $definition = $this->definitionRepository->findById($defId);

        if (null === $definition) {
            throw ProcessDefinitionNotFoundException::withId($query->processDefinitionId);
        }

        $nodes = $this->nodeRepository->findByProcessDefinitionId($defId);
        $transitions = $this->transitionRepository->findByProcessDefinitionId($defId);

        return new ProcessDefinitionDetailDTO(
            definition: ProcessDefinitionDTO::fromEntity($definition),
            nodes: array_map(static fn ($n) => NodeDTO::fromEntity($n), $nodes),
            transitions: array_map(static fn ($t) => TransitionDTO::fromEntity($t), $transitions),
        );
    }
}
