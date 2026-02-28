<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Application\Command\ExecuteTaskAction;

use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\TaskManager\Application\Command\TransitionTask\TransitionTaskCommand;
use App\Workflow\Application\Command\ExecuteTaskAction\ExecuteTaskActionCommand;
use App\Workflow\Application\Command\ExecuteTaskAction\ExecuteTaskActionHandler;
use App\Workflow\Application\Service\FormFieldCollector;
use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\Entity\WorkflowTaskLink;
use App\Workflow\Domain\Exception\FormValidationException;
use App\Workflow\Domain\Exception\WorkflowExecutionException;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use App\Workflow\Domain\Service\ExpressionEvaluator;
use App\Workflow\Domain\Service\FormSchemaValidator;
use App\Workflow\Domain\Service\ProcessGraph;
use App\Workflow\Domain\Service\WorkflowEngine;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

final class ExecuteTaskActionHandlerTest extends TestCase
{
    private string $startNodeId;
    private string $taskNodeId;
    private string $endNodeId;
    private string $transitionToTaskId;
    private string $transitionToEndId;

    protected function setUp(): void
    {
        $this->startNodeId = Uuid::v4()->toRfc4122();
        $this->taskNodeId = Uuid::v4()->toRfc4122();
        $this->endNodeId = Uuid::v4()->toRfc4122();
        $this->transitionToTaskId = Uuid::v4()->toRfc4122();
        $this->transitionToEndId = Uuid::v4()->toRfc4122();
    }

    public function testCompleteTaskWithValidFormData(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $taskId = Uuid::v4()->toRfc4122();

        $nodesSnapshot = $this->buildNodesSnapshot();
        $instance = $this->buildRunningInstanceAtTaskNode($processInstanceId, $versionId);
        $tokenId = $instance->activeTokens()[0]->id()->value();

        $link = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: $processInstanceId,
            tokenId: $tokenId,
            taskId: $taskId,
        );

        $linkRepo = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkRepo->method('findByTaskId')->willReturn($link);

        $instanceRepo = $this->createMock(ProcessInstanceRepositoryInterface::class);
        $instanceRepo->method('findById')->willReturn($instance);
        $instanceRepo->expects($this->once())->method('save');

        $versionRepo = $this->createStub(ProcessDefinitionVersionRepositoryInterface::class);
        $versionRepo->method('findById')->willReturn(
            ProcessDefinitionVersion::create(
                ProcessDefinitionVersionId::fromString($versionId),
                ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
                1,
                $nodesSnapshot,
                'publisher-1',
            ),
        );

        // Track dispatched commands
        $dispatchedCommands = [];
        $commandBus = $this->createStub(CommandBusInterface::class);
        $commandBus->method('dispatch')->willReturnCallback(
            function (CommandInterface $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            },
        );

        $handler = new ExecuteTaskActionHandler(
            $linkRepo,
            $instanceRepo,
            $versionRepo,
            new WorkflowEngine(new ExpressionEvaluator(new NullLogger())),
            new FormFieldCollector(),
            new FormSchemaValidator(),
            $commandBus,
        );

        $handler(new ExecuteTaskActionCommand(
            taskId: $taskId,
            actionKey: 'approve',
            formData: ['comment' => 'Looks good'],
        ));

        // Verify link is marked completed
        $this->assertTrue($link->isCompleted());

        // Verify TransitionTaskCommand was dispatched
        $transitionCommand = null;
        foreach ($dispatchedCommands as $cmd) {
            if ($cmd instanceof TransitionTaskCommand) {
                $transitionCommand = $cmd;
                break;
            }
        }
        $this->assertNotNull($transitionCommand, 'TransitionTaskCommand should have been dispatched');
        $this->assertSame($taskId, $transitionCommand->taskId);
        $this->assertSame('workflow_complete', $transitionCommand->transition);
    }

    public function testCompleteTaskWithValidationErrors(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $taskId = Uuid::v4()->toRfc4122();

        $nodesSnapshot = $this->buildNodesSnapshotWithRequiredField();
        $instance = $this->buildRunningInstanceAtTaskNode($processInstanceId, $versionId);
        $tokenId = $instance->activeTokens()[0]->id()->value();

        $link = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: $processInstanceId,
            tokenId: $tokenId,
            taskId: $taskId,
        );

        $linkRepo = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkRepo->method('findByTaskId')->willReturn($link);

        $instanceRepo = $this->createStub(ProcessInstanceRepositoryInterface::class);
        $instanceRepo->method('findById')->willReturn($instance);

        $versionRepo = $this->createStub(ProcessDefinitionVersionRepositoryInterface::class);
        $versionRepo->method('findById')->willReturn(
            ProcessDefinitionVersion::create(
                ProcessDefinitionVersionId::fromString($versionId),
                ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
                1,
                $nodesSnapshot,
                'publisher-1',
            ),
        );

        $commandBus = $this->createStub(CommandBusInterface::class);

        $handler = new ExecuteTaskActionHandler(
            $linkRepo,
            $instanceRepo,
            $versionRepo,
            new WorkflowEngine(new ExpressionEvaluator(new NullLogger())),
            new FormFieldCollector(),
            new FormSchemaValidator(),
            $commandBus,
        );

        $this->expectException(FormValidationException::class);

        // Submit empty formData when 'reason' field is required
        $handler(new ExecuteTaskActionCommand(
            taskId: $taskId,
            actionKey: 'approve',
            formData: [],
        ));
    }

    public function testCompleteTaskWhenLinkNotFound(): void
    {
        $linkRepo = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkRepo->method('findByTaskId')->willReturn(null);

        $handler = new ExecuteTaskActionHandler(
            $linkRepo,
            $this->createStub(ProcessInstanceRepositoryInterface::class),
            $this->createStub(ProcessDefinitionVersionRepositoryInterface::class),
            new WorkflowEngine(new ExpressionEvaluator(new NullLogger())),
            new FormFieldCollector(),
            new FormSchemaValidator(),
            $this->createStub(CommandBusInterface::class),
        );

        $this->expectException(WorkflowExecutionException::class);

        $handler(new ExecuteTaskActionCommand(
            taskId: Uuid::v4()->toRfc4122(),
            actionKey: 'approve',
            formData: [],
        ));
    }

    public function testCompleteTaskWhenAlreadyCompleted(): void
    {
        $link = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: Uuid::v4()->toRfc4122(),
            tokenId: Uuid::v4()->toRfc4122(),
            taskId: Uuid::v4()->toRfc4122(),
        );
        $link->markCompleted();

        $linkRepo = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkRepo->method('findByTaskId')->willReturn($link);

        $handler = new ExecuteTaskActionHandler(
            $linkRepo,
            $this->createStub(ProcessInstanceRepositoryInterface::class),
            $this->createStub(ProcessDefinitionVersionRepositoryInterface::class),
            new WorkflowEngine(new ExpressionEvaluator(new NullLogger())),
            new FormFieldCollector(),
            new FormSchemaValidator(),
            $this->createStub(CommandBusInterface::class),
        );

        $this->expectException(WorkflowExecutionException::class);

        $handler(new ExecuteTaskActionCommand(
            taskId: $link->taskId(),
            actionKey: 'approve',
            formData: [],
        ));
    }

    public function testTaskTransitionFailureDoesNotBreakCompletion(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $taskId = Uuid::v4()->toRfc4122();

        $nodesSnapshot = $this->buildNodesSnapshot();
        $instance = $this->buildRunningInstanceAtTaskNode($processInstanceId, $versionId);
        $tokenId = $instance->activeTokens()[0]->id()->value();

        $link = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: $processInstanceId,
            tokenId: $tokenId,
            taskId: $taskId,
        );

        $linkRepo = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkRepo->method('findByTaskId')->willReturn($link);

        $instanceRepo = $this->createStub(ProcessInstanceRepositoryInterface::class);
        $instanceRepo->method('findById')->willReturn($instance);

        $versionRepo = $this->createStub(ProcessDefinitionVersionRepositoryInterface::class);
        $versionRepo->method('findById')->willReturn(
            ProcessDefinitionVersion::create(
                ProcessDefinitionVersionId::fromString($versionId),
                ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
                1,
                $nodesSnapshot,
                'publisher-1',
            ),
        );

        // CommandBus throws on TransitionTaskCommand dispatch
        $commandBus = $this->createStub(CommandBusInterface::class);
        $commandBus->method('dispatch')->willReturnCallback(
            function (CommandInterface $command): void {
                if ($command instanceof TransitionTaskCommand) {
                    throw new \RuntimeException('Task transition failed');
                }
            },
        );

        $handler = new ExecuteTaskActionHandler(
            $linkRepo,
            $instanceRepo,
            $versionRepo,
            new WorkflowEngine(new ExpressionEvaluator(new NullLogger())),
            new FormFieldCollector(),
            new FormSchemaValidator(),
            $commandBus,
        );

        // Should NOT throw — transition failure is caught silently
        $handler(new ExecuteTaskActionCommand(
            taskId: $taskId,
            actionKey: 'approve',
            formData: ['comment' => 'Done'],
        ));

        // Verify the link was still marked completed despite transition failure
        $this->assertTrue($link->isCompleted());
    }

    /**
     * Build a ProcessInstance that has a token waiting at the task node.
     *
     * We simulate: start -> advanceToken (moves to task node) -> task node activates (token waits)
     */
    private function buildRunningInstanceAtTaskNode(string $processInstanceId, string $versionId): ProcessInstance
    {
        $instance = ProcessInstance::start(
            id: ProcessInstanceId::fromString($processInstanceId),
            processDefinitionId: ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
            versionId: ProcessDefinitionVersionId::fromString($versionId),
            organizationId: 'org-1',
            startedBy: 'user-1',
            variables: [],
            startNodeId: NodeId::fromString($this->startNodeId),
        );

        // Advance the token through the start node to the task node using the engine
        $graph = ProcessGraph::fromSnapshot($this->buildNodesSnapshot());
        $engine = new WorkflowEngine(new ExpressionEvaluator(new NullLogger()));
        $tokenId = $instance->activeTokens()[0]->id();
        $engine->advanceToken($instance, $tokenId, $graph);

        // Clear uncommitted events to avoid interference with test assertions
        $instance->clearUncommittedEvents();

        return $instance;
    }

    /**
     * Build a minimal nodes snapshot: Start -> Task -> End.
     * Task node has an optional 'comment' field.
     *
     * @return array<string, mixed>
     */
    private function buildNodesSnapshot(): array
    {
        return [
            'nodes' => [
                ['id' => $this->startNodeId, 'type' => 'start', 'name' => 'Start', 'config' => []],
                [
                    'id' => $this->taskNodeId,
                    'type' => 'task',
                    'name' => 'Review',
                    'config' => [
                        'formFields' => [
                            ['name' => 'comment', 'label' => 'Comment', 'type' => 'text', 'required' => false],
                        ],
                    ],
                ],
                ['id' => $this->endNodeId, 'type' => 'end', 'name' => 'End', 'config' => []],
            ],
            'transitions' => [
                [
                    'id' => $this->transitionToTaskId,
                    'source_node_id' => $this->startNodeId,
                    'target_node_id' => $this->taskNodeId,
                    'action_key' => '',
                    'name' => '',
                    'form_fields' => [],
                    'sort_order' => 0,
                ],
                [
                    'id' => $this->transitionToEndId,
                    'source_node_id' => $this->taskNodeId,
                    'target_node_id' => $this->endNodeId,
                    'action_key' => 'approve',
                    'name' => 'Approve',
                    'form_fields' => [],
                    'sort_order' => 0,
                ],
            ],
        ];
    }

    /**
     * Build nodes snapshot with a required field on the task node.
     *
     * @return array<string, mixed>
     */
    private function buildNodesSnapshotWithRequiredField(): array
    {
        return [
            'nodes' => [
                ['id' => $this->startNodeId, 'type' => 'start', 'name' => 'Start', 'config' => []],
                [
                    'id' => $this->taskNodeId,
                    'type' => 'task',
                    'name' => 'Review',
                    'config' => [
                        'formFields' => [
                            ['name' => 'reason', 'label' => 'Reason', 'type' => 'text', 'required' => true],
                        ],
                    ],
                ],
                ['id' => $this->endNodeId, 'type' => 'end', 'name' => 'End', 'config' => []],
            ],
            'transitions' => [
                [
                    'id' => $this->transitionToTaskId,
                    'source_node_id' => $this->startNodeId,
                    'target_node_id' => $this->taskNodeId,
                    'action_key' => '',
                    'name' => '',
                    'form_fields' => [],
                    'sort_order' => 0,
                ],
                [
                    'id' => $this->transitionToEndId,
                    'source_node_id' => $this->taskNodeId,
                    'target_node_id' => $this->endNodeId,
                    'action_key' => 'approve',
                    'name' => 'Approve',
                    'form_fields' => [],
                    'sort_order' => 0,
                ],
            ],
        ];
    }
}
