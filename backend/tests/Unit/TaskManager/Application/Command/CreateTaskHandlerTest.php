<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManager\Application\Command;

use App\TaskManager\Application\Command\CreateTask\CreateTaskCommand;
use App\TaskManager\Application\Command\CreateTask\CreateTaskHandler;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Application\Service\AssignmentResolver;
use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\AssignmentStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateTaskHandlerTest extends TestCase
{
    public function testUnassignedStrategyCreatesTaskWithNoAssignment(): void
    {
        $savedTask = null;
        $taskRepo = $this->createMock(TaskRepositoryInterface::class);
        $taskRepo->expects($this->once())->method('save')->with(
            $this->callback(function (Task $task) use (&$savedTask): bool {
                $savedTask = $task;

                return true;
            }),
        );

        // AssignmentResolver is final readonly -- build a real one with a stub port
        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $resolver = new AssignmentResolver($orgPort);

        $handler = new CreateTaskHandler($taskRepo, $resolver);

        $command = new CreateTaskCommand(
            id: Uuid::v4()->toRfc4122(),
            organizationId: 'org-1',
            title: 'Test Task',
            description: null,
            priority: 'medium',
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
            assignmentStrategy: 'unassigned',
        );

        $handler($command);

        $this->assertNotNull($savedTask);
        $this->assertSame(AssignmentStrategy::Unassigned, $savedTask->assignmentStrategy());
        $this->assertNull($savedTask->assigneeId());
        $this->assertNull($savedTask->candidateRoleId());
        $this->assertNull($savedTask->candidateDepartmentId());
    }

    public function testNonUnassignedStrategyDelegatesToAssignmentResolver(): void
    {
        $savedTask = null;
        $taskRepo = $this->createMock(TaskRepositoryInterface::class);
        $taskRepo->expects($this->once())->method('save')->with(
            $this->callback(function (Task $task) use (&$savedTask): bool {
                $savedTask = $task;

                return true;
            }),
        );

        // Configure OrganizationQueryPort to return multiple candidates (pool task)
        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('findActiveEmployeeIdsByRoleId')->willReturn([
            ['employeeId' => 'emp-1'],
            ['employeeId' => 'emp-2'],
        ]);
        $resolver = new AssignmentResolver($orgPort);

        $handler = new CreateTaskHandler($taskRepo, $resolver);

        $command = new CreateTaskCommand(
            id: Uuid::v4()->toRfc4122(),
            organizationId: 'org-1',
            title: 'Review PR',
            description: 'Please review the pull request',
            priority: 'high',
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
            assignmentStrategy: 'by_role',
            assigneeRoleId: 'role-dev',
        );

        $handler($command);

        $this->assertNotNull($savedTask);
        $this->assertSame(AssignmentStrategy::ByRole, $savedTask->assignmentStrategy());
        $this->assertNull($savedTask->assigneeId(), 'Pool task with 2 candidates should not have assignee');
        $this->assertSame('role-dev', $savedTask->candidateRoleId());
        $this->assertNull($savedTask->candidateDepartmentId());
    }

    public function testFormSchemaIsPassedToTaskCreate(): void
    {
        $savedTask = null;
        $taskRepo = $this->createMock(TaskRepositoryInterface::class);
        $taskRepo->expects($this->once())->method('save')->with(
            $this->callback(function (Task $task) use (&$savedTask): bool {
                $savedTask = $task;

                return true;
            }),
        );

        $resolver = new AssignmentResolver($this->createStub(OrganizationQueryPort::class));

        $handler = new CreateTaskHandler($taskRepo, $resolver);

        $formSchema = [
            'shared_fields' => [
                ['name' => 'comment', 'label' => 'Comment', 'type' => 'text', 'required' => false],
            ],
            'actions' => [
                [
                    'key' => 'approve',
                    'label' => 'Approve',
                    'form_fields' => [
                        ['name' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
                    ],
                ],
            ],
        ];

        $command = new CreateTaskCommand(
            id: Uuid::v4()->toRfc4122(),
            organizationId: 'org-1',
            title: 'Approval Task',
            description: null,
            priority: 'medium',
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
            formSchema: $formSchema,
        );

        $handler($command);

        $this->assertNotNull($savedTask);
        $this->assertNotNull($savedTask->formSchema(), 'Task should have formSchema set');
        $this->assertSame($formSchema, $savedTask->formSchema());
        $this->assertSame('comment', $savedTask->formSchema()['shared_fields'][0]['name']);
        $this->assertSame('approve', $savedTask->formSchema()['actions'][0]['key']);
    }
}
