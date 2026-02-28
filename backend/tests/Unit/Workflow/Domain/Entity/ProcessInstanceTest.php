<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Domain\Entity;

use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProcessInstanceTest extends TestCase
{
    private function createInstance(array $initialVariables = []): ProcessInstance
    {
        return ProcessInstance::start(
            id: ProcessInstanceId::generate(),
            processDefinitionId: ProcessDefinitionId::generate(),
            versionId: ProcessDefinitionVersionId::generate(),
            organizationId: 'org-001',
            startedBy: 'user-001',
            variables: $initialVariables,
            startNodeId: NodeId::generate(),
        );
    }

    #[Test]
    public function itMergesVariablesWithNamespacing(): void
    {
        $instance = $this->createInstance();

        $instance->mergeVariables('node_review', 'approve', [
            'decision' => 'Approved',
            'comment' => 'LGTM',
        ]);

        $vars = $instance->variables();

        // Namespaced storage
        self::assertSame('Approved', $vars['stages']['node_review']['approve']['decision']);
        self::assertSame('LGTM', $vars['stages']['node_review']['approve']['comment']);

        // Flat aliases
        self::assertSame('Approved', $vars['decision']);
        self::assertSame('LGTM', $vars['comment']);
    }

    #[Test]
    public function itPreservesNamespacedDataAcrossMultipleMerges(): void
    {
        $instance = $this->createInstance();

        $instance->mergeVariables('node_review', 'approve', ['decision' => 'Approved']);
        $instance->mergeVariables('node_edit', 'submit', ['title' => 'New Title']);

        $vars = $instance->variables();

        // Both namespaced entries exist
        self::assertSame('Approved', $vars['stages']['node_review']['approve']['decision']);
        self::assertSame('New Title', $vars['stages']['node_edit']['submit']['title']);
    }

    #[Test]
    public function itOverwritesFlatAliasesWithLatestValues(): void
    {
        $instance = $this->createInstance();

        $instance->mergeVariables('node_review', 'approve', ['decision' => 'Approved']);
        $instance->mergeVariables('node_final', 'reject', ['decision' => 'Rejected']);

        $vars = $instance->variables();

        // Flat alias has latest value (last-writer-wins)
        self::assertSame('Rejected', $vars['decision']);

        // But namespaced has both preserved
        self::assertSame('Approved', $vars['stages']['node_review']['approve']['decision']);
        self::assertSame('Rejected', $vars['stages']['node_final']['reject']['decision']);
    }

    #[Test]
    public function itHandlesEmptyDataGracefully(): void
    {
        $instance = $this->createInstance(['existing' => 'value']);

        $instance->mergeVariables('node_1', 'action_1', []);

        $vars = $instance->variables();

        // Variables unchanged
        self::assertSame('value', $vars['existing']);
        self::assertArrayNotHasKey('stages', $vars);
    }

    #[Test]
    public function itPreservesExistingVariablesOnMerge(): void
    {
        $instance = $this->createInstance([
            'requestType' => 'leave',
            'priority' => 'high',
        ]);

        $instance->mergeVariables('node_review', 'approve', ['decision' => 'Approved']);

        $vars = $instance->variables();

        // Initial variables preserved
        self::assertSame('leave', $vars['requestType']);
        self::assertSame('high', $vars['priority']);

        // New namespaced + flat aliases present
        self::assertSame('Approved', $vars['stages']['node_review']['approve']['decision']);
        self::assertSame('Approved', $vars['decision']);
    }

    #[Test]
    public function itUsesDeepMergeForNamespacedStorage(): void
    {
        $instance = $this->createInstance();

        $instance->mergeVariables('node_a', 'action_1', ['field_a' => 'A']);
        $instance->mergeVariables('node_b', 'action_2', ['field_b' => 'B']);
        $instance->mergeVariables('node_c', 'action_3', ['field_c' => 'C']);

        $vars = $instance->variables();

        // All three node entries exist under stages (deep merge, not overwrite)
        self::assertArrayHasKey('node_a', $vars['stages']);
        self::assertArrayHasKey('node_b', $vars['stages']);
        self::assertArrayHasKey('node_c', $vars['stages']);

        self::assertSame('A', $vars['stages']['node_a']['action_1']['field_a']);
        self::assertSame('B', $vars['stages']['node_b']['action_2']['field_b']);
        self::assertSame('C', $vars['stages']['node_c']['action_3']['field_c']);
    }
}
