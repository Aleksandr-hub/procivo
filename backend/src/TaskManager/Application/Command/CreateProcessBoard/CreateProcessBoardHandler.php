<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateProcessBoard;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\TaskManager\Domain\Entity\Board;
use App\TaskManager\Domain\Entity\BoardColumn;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use App\TaskManager\Domain\ValueObject\ColumnId;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateProcessBoardHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
        private BoardColumnRepositoryInterface $columnRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
    ) {
    }

    public function __invoke(CreateProcessBoardCommand $command): void
    {
        $definitionId = ProcessDefinitionId::fromString($command->processDefinitionId);
        $version = $this->versionRepository->findLatestByProcessDefinitionId($definitionId);

        if (null === $version) {
            throw new \InvalidArgumentException(
                sprintf('No published version found for process definition "%s".', $command->processDefinitionId)
            );
        }

        $snapshot = $version->nodesSnapshot();
        $nodes = $snapshot['nodes'] ?? [];
        $transitions = $snapshot['transitions'] ?? [];

        $orderedTaskNodes = $this->topologicalTaskOrder($nodes, $transitions);

        $board = Board::createProcessBoard(
            id: BoardId::fromString($command->id),
            organizationId: OrganizationId::fromString($command->organizationId),
            name: $command->name,
            processDefinitionId: $command->processDefinitionId,
        );

        $this->boardRepository->save($board);

        $boardId = BoardId::fromString($command->id);
        $position = 0;

        foreach ($orderedTaskNodes as $node) {
            $column = BoardColumn::create(
                id: ColumnId::generate(),
                boardId: $boardId,
                name: $node['name'] ?? 'Task',
                position: $position,
                statusMapping: null,
                wipLimit: null,
                color: null,
                nodeId: $node['id'],
            );
            $this->columnRepository->save($column);
            ++$position;
        }

        // Add final "Completed" column
        $completedColumn = BoardColumn::create(
            id: ColumnId::generate(),
            boardId: $boardId,
            name: 'Completed',
            position: $position,
            statusMapping: null,
            wipLimit: null,
            color: null,
            nodeId: null,
        );
        $this->columnRepository->save($completedColumn);
    }

    /**
     * Kahn's topological sort — returns task nodes in topological order.
     *
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array<string, mixed>> $transitions
     *
     * @return list<array<string, mixed>>
     */
    private function topologicalTaskOrder(array $nodes, array $transitions): array
    {
        // Build adjacency list and in-degree map
        /** @var array<string, list<string>> $adjacency */
        $adjacency = [];
        /** @var array<string, int> $inDegree */
        $inDegree = [];

        foreach ($nodes as $node) {
            $nodeId = (string) $node['id'];
            $adjacency[$nodeId] = [];
            $inDegree[$nodeId] = 0;
        }

        foreach ($transitions as $transition) {
            $source = (string) ($transition['source_node_id'] ?? '');
            $target = (string) ($transition['target_node_id'] ?? '');

            if (isset($adjacency[$source], $inDegree[$target])) {
                $adjacency[$source][] = $target;
                ++$inDegree[$target];
            }
        }

        // BFS from nodes with in-degree 0
        $queue = [];
        foreach ($inDegree as $nodeId => $degree) {
            if (0 === $degree) {
                $queue[] = $nodeId;
            }
        }

        /** @var list<string> $sortedIds */
        $sortedIds = [];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $sortedIds[] = $current;

            foreach ($adjacency[$current] as $neighbor) {
                --$inDegree[$neighbor];
                if (0 === $inDegree[$neighbor]) {
                    $queue[] = $neighbor;
                }
            }
        }

        // Build node map for quick lookup
        /** @var array<string, array<string, mixed>> $nodeMap */
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[(string) $node['id']] = $node;
        }

        // Filter to task nodes only, in topological order
        /** @var list<array<string, mixed>> $orderedTaskNodes */
        $orderedTaskNodes = [];
        foreach ($sortedIds as $nodeId) {
            $node = $nodeMap[$nodeId] ?? null;
            if (null !== $node && ($node['type'] ?? '') === 'task') {
                $orderedTaskNodes[] = $node;
            }
        }

        // Fallback for cycles: add remaining task nodes sorted by position_x
        $visitedIds = array_flip($sortedIds);
        $remaining = [];
        foreach ($nodes as $node) {
            $nodeId = (string) $node['id'];
            if (!isset($visitedIds[$nodeId]) && ($node['type'] ?? '') === 'task') {
                $remaining[] = $node;
            }
        }

        usort($remaining, static fn (array $a, array $b): int => ($a['position_x'] ?? 0) <=> ($b['position_x'] ?? 0));

        return array_merge($orderedTaskNodes, $remaining);
    }
}
