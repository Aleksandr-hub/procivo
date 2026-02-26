<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\CreateProcessDefinition;

use App\Workflow\Domain\Entity\ProcessDefinition;
use App\Workflow\Domain\Repository\ProcessDefinitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateProcessDefinitionHandler
{
    public function __construct(
        private ProcessDefinitionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateProcessDefinitionCommand $command): void
    {
        $definition = ProcessDefinition::create(
            id: ProcessDefinitionId::fromString($command->id),
            organizationId: $command->organizationId,
            name: $command->name,
            description: $command->description,
            createdBy: $command->createdBy,
        );

        $this->repository->save($definition);
    }
}
