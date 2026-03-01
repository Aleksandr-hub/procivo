<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\ListVersions;

use App\Workflow\Application\DTO\ProcessDefinitionVersionDTO;
use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListVersionsHandler
{
    public function __construct(
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
    ) {
    }

    /**
     * @return list<ProcessDefinitionVersionDTO>
     */
    public function __invoke(ListVersionsQuery $query): array
    {
        $defId = ProcessDefinitionId::fromString($query->processDefinitionId);
        $versions = $this->versionRepository->findByProcessDefinitionId($defId);

        // Sort by version number descending (newest first)
        usort($versions, static fn (ProcessDefinitionVersion $a, ProcessDefinitionVersion $b) => $b->versionNumber() <=> $a->versionNumber());

        return array_map(
            static fn (ProcessDefinitionVersion $v) => ProcessDefinitionVersionDTO::fromEntity($v),
            $versions,
        );
    }
}
