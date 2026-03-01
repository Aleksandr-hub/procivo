<?php

declare(strict_types=1);

namespace App\Workflow\Application\Query\ListVersions;

use App\Workflow\Application\DTO\ProcessDefinitionVersionDTO;
use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListVersionsHandler
{
    public function __construct(
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private Connection $connection,
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

        // Fetch running instance counts per version via DBAL
        $versionIds = array_map(
            static fn (ProcessDefinitionVersion $v) => $v->id()->value(),
            $versions,
        );

        /** @var array<string, int> $countsByVersion */
        $countsByVersion = [];
        if ([] !== $versionIds) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT version_id, COUNT(*) as cnt FROM workflow_process_instances_view WHERE version_id IN (:ids) AND status = :status GROUP BY version_id',
                ['ids' => $versionIds, 'status' => 'running'],
                ['ids' => ArrayParameterType::STRING],
            );
            foreach ($rows as $row) {
                $countsByVersion[(string) $row['version_id']] = (int) $row['cnt'];
            }
        }

        return array_map(
            static fn (ProcessDefinitionVersion $v) => ProcessDefinitionVersionDTO::fromEntity(
                $v,
                $countsByVersion[$v->id()->value()] ?? 0,
            ),
            $versions,
        );
    }
}
