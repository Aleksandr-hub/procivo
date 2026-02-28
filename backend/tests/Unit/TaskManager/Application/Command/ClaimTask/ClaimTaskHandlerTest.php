<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManager\Application\Command\ClaimTask;

use App\TaskManager\Application\Command\ClaimTask\ClaimTaskCommand;
use App\TaskManager\Application\Command\ClaimTask\ClaimTaskHandler;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Exception\TaskClaimException;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskPriority;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ClaimTaskHandlerTest extends TestCase
{
    public function testClaimPoolTaskSuccessfully(): void
    {
        $taskId = Uuid::v4()->toRfc4122();
        $employeeId = 'emp-1';
        $roleId = 'role-dev';

        $task = Task::create(
            id: TaskId::fromString($taskId),
            organizationId: 'org-1',
            title: 'Pool Task',
            description: null,
            priority: TaskPriority::Medium,
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
            candidateRoleId: $roleId,
        );

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('employeeBelongsToRole')->willReturn(true);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new ClaimTaskHandler($taskRepo, $orgPort, $entityManager);
        $handler(new ClaimTaskCommand($taskId, $employeeId));

        $this->assertSame($employeeId, $task->assigneeId());
    }

    public function testClaimAlreadyClaimedTask(): void
    {
        $taskId = Uuid::v4()->toRfc4122();

        $task = Task::create(
            id: TaskId::fromString($taskId),
            organizationId: 'org-1',
            title: 'Pool Task',
            description: null,
            priority: TaskPriority::Medium,
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
            candidateRoleId: 'role-dev',
        );
        // Claim the task first
        $task->claim('emp-existing');

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $orgPort = $this->createStub(OrganizationQueryPort::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new ClaimTaskHandler($taskRepo, $orgPort, $entityManager);

        $this->expectException(TaskClaimException::class);
        $this->expectExceptionMessageMatches('/already claimed/i');

        $handler(new ClaimTaskCommand($taskId, 'emp-2'));
    }

    public function testClaimNonPoolTask(): void
    {
        $taskId = Uuid::v4()->toRfc4122();

        $task = Task::create(
            id: TaskId::fromString($taskId),
            organizationId: 'org-1',
            title: 'Non-pool Task',
            description: null,
            priority: TaskPriority::Medium,
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
        );

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $orgPort = $this->createStub(OrganizationQueryPort::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new ClaimTaskHandler($taskRepo, $orgPort, $entityManager);

        $this->expectException(TaskClaimException::class);
        $this->expectExceptionMessageMatches('/not a pool task/i');

        $handler(new ClaimTaskCommand($taskId, 'emp-1'));
    }

    public function testClaimTaskNotFound(): void
    {
        $taskId = Uuid::v4()->toRfc4122();

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn(null);

        $orgPort = $this->createStub(OrganizationQueryPort::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new ClaimTaskHandler($taskRepo, $orgPort, $entityManager);

        $this->expectException(TaskNotFoundException::class);

        $handler(new ClaimTaskCommand($taskId, 'emp-1'));
    }

    public function testClaimIneligibleEmployee(): void
    {
        $taskId = Uuid::v4()->toRfc4122();

        $task = Task::create(
            id: TaskId::fromString($taskId),
            organizationId: 'org-1',
            title: 'Pool Task',
            description: null,
            priority: TaskPriority::Medium,
            dueDate: null,
            estimatedHours: null,
            creatorId: 'user-1',
            candidateRoleId: 'role-dev',
            candidateDepartmentId: 'dept-eng',
        );

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('employeeBelongsToRole')->willReturn(false);
        $orgPort->method('employeeBelongsToDepartment')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new ClaimTaskHandler($taskRepo, $orgPort, $entityManager);

        $this->expectException(TaskClaimException::class);
        $this->expectExceptionMessageMatches('/not eligible/i');

        $handler(new ClaimTaskCommand($taskId, 'emp-outsider'));
    }
}
