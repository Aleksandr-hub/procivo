<?php

declare(strict_types=1);

namespace App\Workflow\Application\Service;

use App\Workflow\Domain\Service\ProcessGraph;

final readonly class FormFieldCollector
{
    /**
     * Collects all form fields for a given action: shared (node) + action-specific (transition) + auto-injected (assignee for downstream from_variable nodes).
     *
     * @return list<array<string, mixed>>
     */
    public function collectForValidation(
        ProcessGraph $graph,
        string $nodeId,
        string $actionKey,
    ): array {
        $nodeConfig = $graph->nodeConfig($nodeId);

        /** @var list<array<string, mixed>> $sharedFields */
        $sharedFields = $nodeConfig['formFields'] ?? [];

        $transition = $graph->findOutgoingTransitionByActionKey($nodeId, $actionKey);
        if (null === $transition) {
            $outgoing = $graph->outgoingTransitions($nodeId);
            if (1 === \count($outgoing)) {
                $transition = $outgoing[0];
            }
        }

        /** @var list<array<string, mixed>> $transitionFields */
        $transitionFields = $transition['form_fields'] ?? [];

        if (null !== $transition) {
            $targetNodeId = (string) ($transition['target_node_id'] ?? '');
            $transitionFields = $this->injectAssigneeFieldsForDownstream($graph, $targetNodeId, $transitionFields);
        }

        return [...$sharedFields, ...$transitionFields];
    }

    /**
     * Enriches transition form fields with auto-injected Employee pickers for downstream from_variable nodes.
     *
     * @param list<array<string, mixed>> $formFields
     *
     * @return list<array<string, mixed>>
     */
    public function injectAssigneeFieldsForDownstream(
        ProcessGraph $graph,
        string $targetNodeId,
        array $formFields,
    ): array {
        if ('' === $targetNodeId) {
            return $formFields;
        }

        $taskNodes = $this->findDownstreamFromVariableNodes($graph, $targetNodeId, []);

        foreach ($taskNodes as $node) {
            $fieldName = '_assignee_for_' . $node['id'];
            $formFields[] = [
                'name' => $fieldName,
                'label' => $node['name'],
                'type' => 'employee',
                'required' => true,
            ];
        }

        return $formFields;
    }

    /**
     * @param array<string, bool> $visited
     *
     * @return list<array{id: string, name: string}>
     */
    private function findDownstreamFromVariableNodes(
        ProcessGraph $graph,
        string $nodeId,
        array $visited,
    ): array {
        if (isset($visited[$nodeId])) {
            return [];
        }
        $visited[$nodeId] = true;

        try {
            $nodeType = $graph->nodeType($nodeId);
        } catch (\RuntimeException) {
            return [];
        }

        if ('task' === $nodeType) {
            $config = $graph->nodeConfig($nodeId);
            if ('from_variable' === ($config['assignment_strategy'] ?? '')) {
                return [['id' => $nodeId, 'name' => $graph->nodeName($nodeId)]];
            }

            return [];
        }

        if (\in_array($nodeType, ['exclusive_gateway', 'parallel_gateway', 'inclusive_gateway'], true)) {
            $results = [];
            foreach ($graph->outgoingTransitions($nodeId) as $transition) {
                $results = [...$results, ...$this->findDownstreamFromVariableNodes(
                    $graph,
                    (string) ($transition['target_node_id'] ?? ''),
                    $visited,
                )];
            }

            return $results;
        }

        return [];
    }
}
