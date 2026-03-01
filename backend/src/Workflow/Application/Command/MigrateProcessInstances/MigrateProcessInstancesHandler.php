<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\MigrateProcessInstances;

use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class MigrateProcessInstancesHandler
{
    public function __construct(
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private Connection $connection,
        private ProcessInstanceRepositoryInterface $processInstanceRepository,
    ) {
    }

    public function __invoke(MigrateProcessInstancesCommand $command): void
    {
        $targetVersion = $this->versionRepository->findById(
            ProcessDefinitionVersionId::fromString($command->targetVersionId),
        );

        if (null === $targetVersion) {
            throw new \DomainException('Target version not found.');
        }

        // Validate that target version belongs to the specified definition
        if ($targetVersion->processDefinitionId()->value() !== $command->processDefinitionId) {
            throw new \DomainException('Target version does not belong to the specified process definition.');
        }

        // Schema compatibility validation: verify that all task node IDs
        // present in currently running instances also exist in the target version.
        // This prevents migrating to a version that removed nodes where tokens are waiting.
        $snapshot = $targetVersion->nodesSnapshot();

        /** @var list<array<string, mixed>> $snapshotNodes */
        $snapshotNodes = $snapshot['nodes'] ?? [];

        $targetNodeIds = array_map(
            static fn (array $node): string => (string) $node['id'],
            $snapshotNodes,
        );

        // Build graph from target version snapshot for action_key validation
        $targetGraph = ProcessGraph::fromSnapshot($snapshot);

        // Find active token node_ids for running instances of this definition
        $runningRows = $this->connection->fetchAllAssociative(
            'SELECT id, tokens FROM workflow_process_instances_view WHERE definition_id = :definitionId AND status = :status',
            ['definitionId' => $command->processDefinitionId, 'status' => 'running'],
        );

        foreach ($runningRows as $row) {
            /** @var array<string, array<string, mixed>> $tokens */
            $tokens = json_decode((string) $row['tokens'], true, 512, \JSON_THROW_ON_ERROR);
            foreach ($tokens as $token) {
                if (isset($token['node_id'], $token['status']) && 'completed' !== $token['status']) {
                    if (!\in_array($token['node_id'], $targetNodeIds, true)) {
                        throw new \DomainException(\sprintf(
                            'Cannot migrate instance %s: active token at node "%s" which does not exist in target version %d.',
                            (string) $row['id'],
                            (string) $token['node_id'],
                            $targetVersion->versionNumber(),
                        ));
                    }

                    // Action_key validation: task nodes must have outgoing transitions in target version
                    if ('task' === $targetGraph->nodeType((string) $token['node_id'])) {
                        $outgoing = $targetGraph->outgoingTransitions((string) $token['node_id']);
                        if (0 === count($outgoing)) {
                            throw new \DomainException(\sprintf(
                                'Cannot migrate instance %s: task node "%s" has no outgoing transitions in target version %d.',
                                (string) $row['id'],
                                (string) $token['node_id'],
                                $targetVersion->versionNumber(),
                            ));
                        }
                    }
                }
            }
        }

        // Event-sourced migration: load each running instance from event store,
        // call migrateToVersion() to record the domain event, then save.
        // save() appends ProcessInstanceMigratedEvent to event store AND dispatches to event.bus,
        // which triggers ProcessInstanceProjection::onProcessInstanceMigrated to update read model.
        $targetVersionIdVO = ProcessDefinitionVersionId::fromString($command->targetVersionId);
        foreach ($runningRows as $row) {
            $instance = $this->processInstanceRepository->findById(
                ProcessInstanceId::fromString((string) $row['id']),
            );
            if (null !== $instance && $instance->isRunning()) {
                $instance->migrateToVersion($targetVersionIdVO, $command->migratedBy);
                $this->processInstanceRepository->save($instance);
            }
        }
    }
}
