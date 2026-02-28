<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Application\Service;

use App\Workflow\Application\Service\FormFieldCollector;
use App\Workflow\Application\Service\FormSchemaBuilder;
use App\Workflow\Domain\Service\ProcessGraph;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormSchemaBuilderTest extends TestCase
{
    private FormSchemaBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FormSchemaBuilder(new FormFieldCollector());
    }

    #[Test]
    public function itReturnsSharedFieldsFromNodeConfig(): void
    {
        $graph = $this->buildGraph(
            nodeConfig: [
                'formFields' => [
                    ['name' => 'comment', 'type' => 'text', 'label' => 'Comment', 'required' => false],
                ],
            ],
            transitions: [
                ['action_key' => 'approve', 'name' => 'Approve', 'form_fields' => []],
            ],
        );

        $schema = $this->builder->build($graph, 'task-1');

        self::assertCount(1, $schema['shared_fields']);
        self::assertSame('comment', $schema['shared_fields'][0]['name']);
    }

    #[Test]
    public function itReturnsActionsFromOutgoingTransitions(): void
    {
        $graph = $this->buildGraph(
            transitions: [
                [
                    'action_key' => 'approve',
                    'name' => 'Approve',
                    'form_fields' => [
                        ['name' => 'reason', 'type' => 'text', 'label' => 'Reason', 'required' => true],
                    ],
                ],
                [
                    'action_key' => 'reject',
                    'name' => 'Reject',
                    'form_fields' => [
                        ['name' => 'rejection_note', 'type' => 'textarea', 'label' => 'Note', 'required' => true],
                    ],
                ],
            ],
        );

        $schema = $this->builder->build($graph, 'task-1');

        self::assertCount(2, $schema['actions']);

        self::assertSame('approve', $schema['actions'][0]['key']);
        self::assertSame('Approve', $schema['actions'][0]['label']);
        self::assertCount(1, $schema['actions'][0]['form_fields']);
        self::assertSame('reason', $schema['actions'][0]['form_fields'][0]['name']);

        self::assertSame('reject', $schema['actions'][1]['key']);
        self::assertSame('Reject', $schema['actions'][1]['label']);
        self::assertCount(1, $schema['actions'][1]['form_fields']);
        self::assertSame('rejection_note', $schema['actions'][1]['form_fields'][0]['name']);
    }

    #[Test]
    public function itDefaultsActionKeyToCompleteWhenMissing(): void
    {
        $graph = $this->buildGraph(
            transitions: [
                ['name' => 'Done', 'form_fields' => []],
            ],
        );

        $schema = $this->builder->build($graph, 'task-1');

        self::assertCount(1, $schema['actions']);
        self::assertSame('complete', $schema['actions'][0]['key']);
    }

    #[Test]
    public function itDefaultsLabelToActionKeyWhenNameMissing(): void
    {
        $graph = $this->buildGraph(
            transitions: [
                ['action_key' => 'submit', 'form_fields' => []],
            ],
        );

        $schema = $this->builder->build($graph, 'task-1');

        self::assertCount(1, $schema['actions']);
        self::assertSame('submit', $schema['actions'][0]['label']);
    }

    #[Test]
    public function itDefaultsLabelToCompleteWhenBothMissing(): void
    {
        $graph = $this->buildGraph(
            transitions: [
                ['form_fields' => []],
            ],
        );

        $schema = $this->builder->build($graph, 'task-1');

        self::assertCount(1, $schema['actions']);
        self::assertSame('complete', $schema['actions'][0]['key']);
        self::assertSame('Complete', $schema['actions'][0]['label']);
    }

    #[Test]
    public function itCallsInjectAssigneeFieldsForDownstream(): void
    {
        // Build a graph where the transition targets a task node with from_variable assignment
        $graph = ProcessGraph::fromSnapshot([
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'name' => 'Start', 'config' => []],
                ['id' => 'task-1', 'type' => 'task', 'name' => 'Review', 'config' => []],
                ['id' => 'task-2', 'type' => 'task', 'name' => 'Execute', 'config' => [
                    'assignment_strategy' => 'from_variable',
                ]],
                ['id' => 'end', 'type' => 'end', 'name' => 'End', 'config' => []],
            ],
            'transitions' => [
                ['source_node_id' => 'start', 'target_node_id' => 'task-1', 'action_key' => 'start', 'name' => 'Start', 'form_fields' => []],
                ['source_node_id' => 'task-1', 'target_node_id' => 'task-2', 'action_key' => 'approve', 'name' => 'Approve', 'form_fields' => []],
                ['source_node_id' => 'task-2', 'target_node_id' => 'end', 'action_key' => 'done', 'name' => 'Done', 'form_fields' => []],
            ],
        ]);

        $schema = $this->builder->build($graph, 'task-1');

        // The 'approve' action targets task-2, which has from_variable assignment
        // So injectAssigneeFieldsForDownstream should add an employee picker field
        self::assertCount(1, $schema['actions']);
        self::assertSame('approve', $schema['actions'][0]['key']);

        $fields = $schema['actions'][0]['form_fields'];
        self::assertCount(1, $fields);
        self::assertSame('_assignee_for_task-2', $fields[0]['name']);
        self::assertSame('employee', $fields[0]['type']);
        self::assertTrue($fields[0]['required']);
    }

    #[Test]
    public function itReturnsEmptySharedFieldsWhenNoFormFields(): void
    {
        $graph = $this->buildGraph(
            nodeConfig: [],
            transitions: [
                ['action_key' => 'complete', 'name' => 'Complete', 'form_fields' => []],
            ],
        );

        $schema = $this->builder->build($graph, 'task-1');

        self::assertSame([], $schema['shared_fields']);
    }

    #[Test]
    public function itReturnsEmptyActionsWhenNoOutgoingTransitions(): void
    {
        // Task node with no outgoing transitions (unusual but possible)
        $graph = ProcessGraph::fromSnapshot([
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'name' => 'Start', 'config' => []],
                ['id' => 'task-1', 'type' => 'task', 'name' => 'Review', 'config' => [
                    'formFields' => [
                        ['name' => 'note', 'type' => 'text', 'label' => 'Note', 'required' => false],
                    ],
                ]],
            ],
            'transitions' => [
                ['source_node_id' => 'start', 'target_node_id' => 'task-1', 'action_key' => 'start', 'name' => 'Start', 'form_fields' => []],
            ],
        ]);

        $schema = $this->builder->build($graph, 'task-1');

        self::assertSame([], $schema['actions']);
        self::assertCount(1, $schema['shared_fields']);
    }

    // =====================
    // Helper
    // =====================

    /**
     * Build a simple graph: start -> task-1 -> end, with configurable task node config and transitions.
     *
     * @param array<string, mixed> $nodeConfig
     * @param list<array<string, mixed>> $transitions outgoing transitions from task-1
     */
    private function buildGraph(array $nodeConfig = [], array $transitions = []): ProcessGraph
    {
        $snapshotTransitions = [
            ['source_node_id' => 'start', 'target_node_id' => 'task-1', 'action_key' => 'begin', 'name' => 'Begin', 'form_fields' => []],
        ];

        foreach ($transitions as $t) {
            $snapshotTransitions[] = array_merge([
                'source_node_id' => 'task-1',
                'target_node_id' => 'end',
                'form_fields' => [],
            ], $t);
        }

        return ProcessGraph::fromSnapshot([
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'name' => 'Start', 'config' => []],
                ['id' => 'task-1', 'type' => 'task', 'name' => 'Review', 'config' => $nodeConfig],
                ['id' => 'end', 'type' => 'end', 'name' => 'End', 'config' => []],
            ],
            'transitions' => $snapshotTransitions,
        ]);
    }
}
