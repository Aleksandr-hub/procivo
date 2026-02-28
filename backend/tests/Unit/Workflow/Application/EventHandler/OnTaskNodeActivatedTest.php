<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Application\EventHandler;

use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Command\CommandInterface;
use App\TaskManager\Application\Command\CreateTask\CreateTaskCommand;
use App\TaskManager\Application\Command\UpdateTask\UpdateTaskCommand;
use App\Workflow\Application\EventHandler\OnTaskNodeActivated;
use App\Workflow\Application\Service\FormFieldCollector;
use App\Workflow\Application\Service\FormSchemaBuilder;
use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use App\Workflow\Domain\Entity\ProcessInstance;
use App\Workflow\Domain\Entity\WorkflowTaskLink;
use App\Workflow\Domain\Event\TaskNodeActivatedEvent;
use App\Workflow\Domain\Repository\ProcessDefinitionVersionRepositoryInterface;
use App\Workflow\Domain\Repository\ProcessInstanceRepositoryInterface;
use App\Workflow\Domain\Repository\WorkflowTaskLinkRepositoryInterface;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;
use App\Workflow\Domain\ValueObject\ProcessInstanceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OnTaskNodeActivatedTest extends TestCase
{
    public function testNewTaskIsCreatedWithFormSchemaFromBuilder(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $nodeId = Uuid::v4()->toRfc4122();
        $endNodeId = Uuid::v4()->toRfc4122();
        $startNodeId = Uuid::v4()->toRfc4122();

        $event = new TaskNodeActivatedEvent(
            processInstanceId: $processInstanceId,
            organizationId: 'org-1',
            nodeId: $nodeId,
            tokenId: Uuid::v4()->toRfc4122(),
            nodeName: 'Review Document',
            taskConfig: [],
        );

        $instanceStub = $this->createStub(ProcessInstanceRepositoryInterface::class);
        $instanceStub->method('findById')->willReturn(
            $this->buildProcessInstance($processInstanceId, $versionId, $startNodeId),
        );

        // Build a version with a snapshot containing our task node and a transition
        $nodesSnapshot = [
            'nodes' => [
                ['id' => $startNodeId, 'type' => 'start', 'name' => 'Start', 'config' => []],
                [
                    'id' => $nodeId,
                    'type' => 'task',
                    'name' => 'Review Document',
                    'config' => [
                        'formFields' => [
                            ['name' => 'comment', 'label' => 'Comment', 'type' => 'text', 'required' => false],
                        ],
                    ],
                ],
                ['id' => $endNodeId, 'type' => 'end', 'name' => 'End', 'config' => []],
            ],
            'transitions' => [
                [
                    'id' => Uuid::v4()->toRfc4122(),
                    'source_node_id' => $nodeId,
                    'target_node_id' => $endNodeId,
                    'action_key' => 'approve',
                    'name' => 'Approve',
                    'form_fields' => [],
                    'sort_order' => 0,
                ],
            ],
        ];

        $versionStub = $this->createStub(ProcessDefinitionVersionRepositoryInterface::class);
        $versionStub->method('findById')->willReturn(
            ProcessDefinitionVersion::create(
                ProcessDefinitionVersionId::fromString($versionId),
                ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
                1,
                $nodesSnapshot,
                'publisher-1',
            ),
        );

        // No existing link -- new task path
        $linkStub = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkStub->method('findLatestByProcessInstanceId')->willReturn(null);

        // Capture the dispatched commands
        $dispatchedCommands = [];
        $commandBusStub = $this->createStub(CommandBusInterface::class);
        $commandBusStub->method('dispatch')->willReturnCallback(
            function (CommandInterface $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            },
        );

        $handler = new OnTaskNodeActivated(
            $commandBusStub,
            $linkStub,
            $instanceStub,
            $versionStub,
            new FormSchemaBuilder(new FormFieldCollector()),
        );

        $handler($event);

        // Find the CreateTaskCommand among dispatched commands
        $createCommand = $this->findCommand($dispatchedCommands, CreateTaskCommand::class);

        $this->assertNotNull($createCommand, 'CreateTaskCommand should have been dispatched');
        $this->assertNotNull($createCommand->formSchema, 'formSchema should not be null');
        $this->assertArrayHasKey('shared_fields', $createCommand->formSchema);
        $this->assertArrayHasKey('actions', $createCommand->formSchema);
        $this->assertCount(1, $createCommand->formSchema['shared_fields']);
        $this->assertSame('comment', $createCommand->formSchema['shared_fields'][0]['name']);
        $this->assertCount(1, $createCommand->formSchema['actions']);
        $this->assertSame('approve', $createCommand->formSchema['actions'][0]['key']);
    }

    public function testNewTaskGetsNullFormSchemaWhenVersionNotFound(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $startNodeId = Uuid::v4()->toRfc4122();

        $event = new TaskNodeActivatedEvent(
            processInstanceId: $processInstanceId,
            organizationId: 'org-1',
            nodeId: Uuid::v4()->toRfc4122(),
            tokenId: Uuid::v4()->toRfc4122(),
            nodeName: 'Review',
            taskConfig: [],
        );

        $instanceStub = $this->createStub(ProcessInstanceRepositoryInterface::class);
        $instanceStub->method('findById')->willReturn(
            $this->buildProcessInstance($processInstanceId, $versionId, $startNodeId),
        );

        // Version not found
        $versionStub = $this->createStub(ProcessDefinitionVersionRepositoryInterface::class);
        $versionStub->method('findById')->willReturn(null);

        $linkStub = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkStub->method('findLatestByProcessInstanceId')->willReturn(null);

        $dispatchedCommands = [];
        $commandBusStub = $this->createStub(CommandBusInterface::class);
        $commandBusStub->method('dispatch')->willReturnCallback(
            function (CommandInterface $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            },
        );

        $handler = new OnTaskNodeActivated(
            $commandBusStub,
            $linkStub,
            $instanceStub,
            $versionStub,
            new FormSchemaBuilder(new FormFieldCollector()),
        );

        $handler($event);

        $createCommand = $this->findCommand($dispatchedCommands, CreateTaskCommand::class);

        $this->assertNotNull($createCommand, 'CreateTaskCommand should have been dispatched');
        $this->assertNull($createCommand->formSchema, 'formSchema should be null when version not found');
    }

    public function testExistingLinkUpdatesTaskWithoutFormSchema(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $startNodeId = Uuid::v4()->toRfc4122();

        $event = new TaskNodeActivatedEvent(
            processInstanceId: $processInstanceId,
            organizationId: 'org-1',
            nodeId: Uuid::v4()->toRfc4122(),
            tokenId: Uuid::v4()->toRfc4122(),
            nodeName: 'Review',
            taskConfig: [],
        );

        $instanceStub = $this->createStub(ProcessInstanceRepositoryInterface::class);
        $instanceStub->method('findById')->willReturn(
            $this->buildProcessInstance($processInstanceId, $versionId, $startNodeId),
        );

        // Existing link found -- update path
        $existingLink = WorkflowTaskLink::create(
            id: Uuid::v4()->toRfc4122(),
            processInstanceId: $processInstanceId,
            tokenId: Uuid::v4()->toRfc4122(),
            taskId: Uuid::v4()->toRfc4122(),
        );

        $linkStub = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkStub->method('findLatestByProcessInstanceId')->willReturn($existingLink);

        $dispatchedCommands = [];
        $commandBusStub = $this->createStub(CommandBusInterface::class);
        $commandBusStub->method('dispatch')->willReturnCallback(
            function (CommandInterface $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            },
        );

        $handler = new OnTaskNodeActivated(
            $commandBusStub,
            $linkStub,
            $instanceStub,
            $this->createStub(ProcessDefinitionVersionRepositoryInterface::class),
            new FormSchemaBuilder(new FormFieldCollector()),
        );

        $handler($event);

        // Should dispatch UpdateTaskCommand, NOT CreateTaskCommand
        $this->assertNotNull(
            $this->findCommand($dispatchedCommands, UpdateTaskCommand::class),
            'UpdateTaskCommand should be dispatched for existing link',
        );
        $this->assertNull(
            $this->findCommand($dispatchedCommands, CreateTaskCommand::class),
            'CreateTaskCommand should NOT be dispatched for existing link',
        );
    }

    public function testAssignmentConfigIsPassedThroughToCreateTaskCommand(): void
    {
        $processInstanceId = Uuid::v4()->toRfc4122();
        $versionId = Uuid::v4()->toRfc4122();
        $nodeId = Uuid::v4()->toRfc4122();
        $endNodeId = Uuid::v4()->toRfc4122();
        $startNodeId = Uuid::v4()->toRfc4122();

        $event = new TaskNodeActivatedEvent(
            processInstanceId: $processInstanceId,
            organizationId: 'org-1',
            nodeId: $nodeId,
            tokenId: Uuid::v4()->toRfc4122(),
            nodeName: 'Assign Review',
            taskConfig: [
                'assignment_strategy' => 'by_role',
                'assignee_role_id' => 'role-mgr-1',
            ],
        );

        $instanceStub = $this->createStub(ProcessInstanceRepositoryInterface::class);
        $instanceStub->method('findById')->willReturn(
            $this->buildProcessInstance($processInstanceId, $versionId, $startNodeId),
        );

        $nodesSnapshot = [
            'nodes' => [
                ['id' => $nodeId, 'type' => 'task', 'name' => 'Assign Review', 'config' => []],
                ['id' => $endNodeId, 'type' => 'end', 'name' => 'End', 'config' => []],
            ],
            'transitions' => [
                [
                    'id' => Uuid::v4()->toRfc4122(),
                    'source_node_id' => $nodeId,
                    'target_node_id' => $endNodeId,
                    'action_key' => 'complete',
                    'name' => 'Complete',
                    'form_fields' => [],
                    'sort_order' => 0,
                ],
            ],
        ];
        $versionStub = $this->createStub(ProcessDefinitionVersionRepositoryInterface::class);
        $versionStub->method('findById')->willReturn(
            ProcessDefinitionVersion::create(
                ProcessDefinitionVersionId::fromString($versionId),
                ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
                1,
                $nodesSnapshot,
                'publisher-1',
            ),
        );

        $linkStub = $this->createStub(WorkflowTaskLinkRepositoryInterface::class);
        $linkStub->method('findLatestByProcessInstanceId')->willReturn(null);

        $dispatchedCommands = [];
        $commandBusStub = $this->createStub(CommandBusInterface::class);
        $commandBusStub->method('dispatch')->willReturnCallback(
            function (CommandInterface $command) use (&$dispatchedCommands): void {
                $dispatchedCommands[] = $command;
            },
        );

        $handler = new OnTaskNodeActivated(
            $commandBusStub,
            $linkStub,
            $instanceStub,
            $versionStub,
            new FormSchemaBuilder(new FormFieldCollector()),
        );

        $handler($event);

        $createCommand = $this->findCommand($dispatchedCommands, CreateTaskCommand::class);

        $this->assertNotNull($createCommand);
        $this->assertSame('by_role', $createCommand->assignmentStrategy);
        $this->assertSame('role-mgr-1', $createCommand->assigneeRoleId);
        $this->assertNull($createCommand->assigneeEmployeeId);
        $this->assertNull($createCommand->assigneeDepartmentId);
    }

    /**
     * Build a ProcessInstance via start() so versionId() is populated.
     */
    private function buildProcessInstance(string $processInstanceId, string $versionId, string $startNodeId): ProcessInstance
    {
        return ProcessInstance::start(
            id: ProcessInstanceId::fromString($processInstanceId),
            processDefinitionId: ProcessDefinitionId::fromString(Uuid::v4()->toRfc4122()),
            versionId: ProcessDefinitionVersionId::fromString($versionId),
            organizationId: 'org-1',
            startedBy: 'user-1',
            variables: [],
            startNodeId: NodeId::fromString($startNodeId),
        );
    }

    /**
     * @template T of CommandInterface
     *
     * @param list<CommandInterface> $commands
     * @param class-string<T>       $class
     *
     * @return T|null
     */
    private function findCommand(array $commands, string $class): ?CommandInterface
    {
        foreach ($commands as $cmd) {
            if ($cmd instanceof $class) {
                return $cmd;
            }
        }

        return null;
    }
}
