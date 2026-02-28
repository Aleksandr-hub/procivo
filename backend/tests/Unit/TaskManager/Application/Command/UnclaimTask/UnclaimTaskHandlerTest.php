<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManager\Application\Command\UnclaimTask;

use App\TaskManager\Application\Command\UnclaimTask\UnclaimTaskCommand;
use App\TaskManager\Application\Command\UnclaimTask\UnclaimTaskHandler;
use App\TaskManager\Domain\Entity\Task;
use App\TaskManager\Domain\Exception\TaskClaimException;
use App\TaskManager\Domain\Exception\TaskNotFoundException;
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use App\TaskManager\Domain\ValueObject\TaskPriority;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UnclaimTaskHandlerTest extends TestCase
{
    public function testUnclaimTaskSuccessfully(): void
    {
        $taskId = Uuid::v4()->toRfc4122();
        $employeeId = 'emp-1';

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
        $task->claim($employeeId);

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new UnclaimTaskHandler($taskRepo, $entityManager);
        $handler(new UnclaimTaskCommand($taskId, $employeeId));

        $this->assertNull($task->assigneeId());
    }

    public function testUnclaimTaskNotFound(): void
    {
        $taskId = Uuid::v4()->toRfc4122();

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new UnclaimTaskHandler($taskRepo, $entityManager);

        $this->expectException(TaskNotFoundException::class);

        $handler(new UnclaimTaskCommand($taskId, 'emp-1'));
    }

    public function testUnclaimNonPoolTask(): void
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
            assigneeId: 'emp-1',
        );

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new UnclaimTaskHandler($taskRepo, $entityManager);

        $this->expectException(TaskClaimException::class);
        $this->expectExceptionMessageMatches('/not a pool task/i');

        $handler(new UnclaimTaskCommand($taskId, 'emp-1'));
    }

    public function testUnclaimByWrongEmployee(): void
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
        $task->claim('emp-owner');

        $taskRepo = $this->createStub(TaskRepositoryInterface::class);
        $taskRepo->method('findByIdForUpdate')->willReturn($task);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(fn (callable $fn) => $fn());

        $handler = new UnclaimTaskHandler($taskRepo, $entityManager);

        $this->expectException(TaskClaimException::class);
        $this->expectExceptionMessageMatches('/not currently claimed/i');

        $handler(new UnclaimTaskCommand($taskId, 'emp-intruder'));
    }
}
