<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\UpdateProcessDefinition;

use App\Workflow\Domain\Exception\ProcessDefinitionNotFoundException;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateProcessDefinitionHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateProcessDefinitionCommand $command): void
    {
        $definition = $this->repository->findById(ProcessDefinitionId::fromString($command->processDefinitionId));

        if (null === $definition) {
            throw ProcessDefinitionNotFoundException::withId($command->processDefinitionId);
        }

        $definition->update($command->name, $command->description);
        $this->repository->save($definition);
    }
}
