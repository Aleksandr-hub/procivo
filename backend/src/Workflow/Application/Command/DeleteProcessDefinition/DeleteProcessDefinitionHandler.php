<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\DeleteProcessDefinition;

use App\Workflow\Domain\Exception\ProcessDefinitionNotFoundException;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteProcessDefinitionHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeleteProcessDefinitionCommand $command): void
    {
        $definition = $this->repository->findById(ProcessDefinitionId::fromString($command->processDefinitionId));

        if (null === $definition) {
            throw ProcessDefinitionNotFoundException::withId($command->processDefinitionId);
        }

        $this->repository->remove($definition);
    }
}
