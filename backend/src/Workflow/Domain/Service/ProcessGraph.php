<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

final class ProcessGraph
{
    /** @var array<string, array<string, mixed>> */
    private array $nodes = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $outgoing = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $incoming = [];

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        $graph = new self();

        /** @var array<string, mixed> $node */
        foreach ($snapshot['nodes'] as $node) {
            $graph->nodes[$node['id']] = $node;
        }

        /** @var array<string, mixed> $transition */
        foreach ($snapshot['transitions'] as $transition) {
            /** @var string $sourceId */
            $sourceId = $transition['source_node_id'];
            /** @var string $targetId */
            $targetId = $transition['target_node_id'];
            $graph->outgoing[$sourceId][] = $transition;
            $graph->incoming[$targetId][] = $transition;
        }

        foreach ($graph->outgoing as &$transitions) {
            usort($transitions, static fn (array $a, array $b): int => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        }

        return $graph;
    }

    public function startNodeId(): string
    {
        foreach ($this->nodes as $node) {
            if ('start' === $node['type']) {
                return $node['id'];
            }
        }

        throw new \RuntimeException('No start node found in process graph.');
    }

    /**
     * @return array<string, mixed>
     */
    public function nodeById(string $id): array
    {
        if (!isset($this->nodes[$id])) {
            throw new \RuntimeException(\sprintf('Node "%s" not found in process graph.', $id));
        }

        return $this->nodes[$id];
    }

    public function nodeType(string $nodeId): string
    {
        return (string) $this->nodeById($nodeId)['type'];
    }

    public function nodeName(string $nodeId): string
    {
        return (string) $this->nodeById($nodeId)['name'];
    }

    /**
     * @return array<string, mixed>
     */
    public function nodeConfig(string $nodeId): array
    {
        /* @var array<string, mixed> */
        return $this->nodeById($nodeId)['config'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function outgoingTransitions(string $nodeId): array
    {
        return $this->outgoing[$nodeId] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function incomingTransitions(string $nodeId): array
    {
        return $this->incoming[$nodeId] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOutgoingTransitionByActionKey(string $nodeId, string $actionKey): ?array
    {
        foreach ($this->outgoingTransitions($nodeId) as $transition) {
            if (($transition['action_key'] ?? null) === $actionKey) {
                return $transition;
            }
        }

        return null;
    }
}
