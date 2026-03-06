<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\SetProcessDefinitionAccess;

use App\Workflow\Domain\Entity\ProcessDefinitionAccess;
use App\Workflow\Domain\Repository\ProcessDefinitionAccessRepositoryInterface;
use App\Workflow\Domain\ValueObject\AccessType;
use App\Workflow\Domain\ValueObject\ProcessDefinitionAccessId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SetProcessDefinitionAccessHandler
{
    public function __construct(
        private ProcessDefinitionAccessRepositoryInterface $accessRepository,
    ) {
    }

    public function __invoke(SetProcessDefinitionAccessCommand $command): void
    {
        $accessType = AccessType::from($command->accessType);

        // Remove existing access rows for this definition + type
        $this->accessRepository->removeByProcessDefinitionIdAndType(
            $command->processDefinitionId,
            $accessType,
        );

        // Create new access entries
        foreach ($command->entries as $entry) {
            $access = ProcessDefinitionAccess::create(
                id: ProcessDefinitionAccessId::generate(),
                processDefinitionId: $command->processDefinitionId,
                organizationId: $command->organizationId,
                departmentId: $entry['departmentId'] ?? null,
                roleId: $entry['roleId'] ?? null,
                accessType: $accessType,
            );

            $this->accessRepository->save($access);
        }
    }
}
