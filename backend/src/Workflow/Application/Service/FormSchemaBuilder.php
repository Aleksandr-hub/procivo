<?php

declare(strict_types=1);

namespace App\Workflow\Application\Service;

use App\Workflow\Domain\Service\ProcessGraph;

final readonly class FormSchemaBuilder
{
    public function __construct(
        private FormFieldCollector $fieldCollector,
    ) {
    }

    /**
     * Build form schema from a ProcessGraph for a given task node.
     *
     * Returns shared (node-level) fields and per-action fields enriched with
     * auto-injected assignee pickers for downstream from_variable nodes.
     *
     * @return array{shared_fields: list<array<string, mixed>>, actions: list<array{key: string, label: string, form_fields: list<array<string, mixed>>}>}
     */
    public function build(ProcessGraph $graph, string $nodeId): array
    {
        $nodeConfig = $graph->nodeConfig($nodeId);
        /** @var list<array<string, mixed>> $sharedFields */
        $sharedFields = $nodeConfig['formFields'] ?? [];

        $outgoing = $graph->outgoingTransitions($nodeId);
        $actions = [];
        foreach ($outgoing as $transition) {
            /** @var list<array<string, mixed>> $formFields */
            $formFields = $transition['form_fields'] ?? [];
            $formFields = $this->fieldCollector->injectAssigneeFieldsForDownstream(
                $graph,
                (string) ($transition['target_node_id'] ?? ''),
                $formFields,
            );

            $actions[] = [
                'key' => $transition['action_key'] ?? 'complete',
                'label' => $transition['name'] ?? $transition['action_key'] ?? 'Complete',
                'form_fields' => $formFields,
            ];
        }

        return [
            'shared_fields' => $sharedFields,
            'actions' => $actions,
        ];
    }
}
