<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\RevertProcessDefinitionToDraft;

use App\Workflow\Domain\Exception\ProcessDefinitionNotFoundException;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RevertProcessDefinitionToDraftHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $definitionRepository,
    ) {
    }

    public function __invoke(RevertProcessDefinitionToDraftCommand $command): void
    {
        $defId = ProcessDefinitionId::fromString($command->processDefinitionId);
        $definition = $this->definitionRepository->findById($defId);

        if (null === $definition) {
            throw ProcessDefinitionNotFoundException::withId($command->processDefinitionId);
        }

        $definition->revertToDraft($command->revertedBy);
        $this->definitionRepository->save($definition);
    }
}
