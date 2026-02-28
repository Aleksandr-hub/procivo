<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManager\Application\Service;

use App\TaskManager\Application\DTO\AssignmentResult;
use App\TaskManager\Application\Port\OrganizationQueryPort;
use App\TaskManager\Application\Service\AssignmentResolver;
use App\TaskManager\Domain\ValueObject\AssignmentStrategy;
use PHPUnit\Framework\TestCase;

final class AssignmentResolverTest extends TestCase
{
    public function testResolveUnassignedReturnsUnassignedStrategyWithNullIds(): void
    {
        $resolver = new AssignmentResolver($this->createStub(OrganizationQueryPort::class));

        $result = $resolver->resolve('unassigned', 'org-1');

        $this->assertSame(AssignmentStrategy::Unassigned, $result->strategy);
        $this->assertNull($result->assigneeId);
        $this->assertNull($result->candidateRoleId);
        $this->assertNull($result->candidateDepartmentId);
    }

    public function testResolveSpecificUserReturnsEmployeeId(): void
    {
        $resolver = new AssignmentResolver($this->createStub(OrganizationQueryPort::class));

        $result = $resolver->resolve('specific_user', 'org-1', employeeId: 'emp-1');

        $this->assertSame(AssignmentStrategy::SpecificUser, $result->strategy);
        $this->assertSame('emp-1', $result->assigneeId);
        $this->assertNull($result->candidateRoleId);
        $this->assertNull($result->candidateDepartmentId);
    }

    public function testResolveByRoleWithMultipleCandidatesReturnsPoolTask(): void
    {
        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('findActiveEmployeeIdsByRoleId')->willReturn([
            ['employeeId' => 'emp-1'],
            ['employeeId' => 'emp-2'],
            ['employeeId' => 'emp-3'],
        ]);

        $resolver = new AssignmentResolver($orgPort);
        $result = $resolver->resolve('by_role', 'org-1', roleId: 'role-1');

        $this->assertSame(AssignmentStrategy::ByRole, $result->strategy);
        $this->assertNull($result->assigneeId, 'Pool task should have no assignee');
        $this->assertSame('role-1', $result->candidateRoleId);
        $this->assertNull($result->candidateDepartmentId);
    }

    public function testResolveByRoleWithSingleCandidateAutoAssigns(): void
    {
        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('findActiveEmployeeIdsByRoleId')->willReturn([
            ['employeeId' => 'emp-1'],
        ]);

        $resolver = new AssignmentResolver($orgPort);
        $result = $resolver->resolve('by_role', 'org-1', roleId: 'role-1');

        $this->assertSame(AssignmentStrategy::ByRole, $result->strategy);
        $this->assertSame('emp-1', $result->assigneeId, 'Single candidate should be auto-assigned');
        $this->assertNull($result->candidateRoleId, 'Auto-assigned task should not have candidateRoleId');
        $this->assertNull($result->candidateDepartmentId);
    }

    public function testResolveByRoleWithNullRoleIdReturnsNullIds(): void
    {
        $resolver = new AssignmentResolver($this->createStub(OrganizationQueryPort::class));

        $result = $resolver->resolve('by_role', 'org-1', roleId: null);

        $this->assertSame(AssignmentStrategy::ByRole, $result->strategy);
        $this->assertNull($result->assigneeId);
        $this->assertNull($result->candidateRoleId);
        $this->assertNull($result->candidateDepartmentId);
    }

    public function testResolveByDepartmentWithMultipleCandidatesReturnsPoolTask(): void
    {
        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('findActiveEmployeeIdsByDepartmentId')->willReturn([
            ['employeeId' => 'emp-1'],
            ['employeeId' => 'emp-2'],
        ]);

        $resolver = new AssignmentResolver($orgPort);
        $result = $resolver->resolve('by_department', 'org-1', departmentId: 'dept-1');

        $this->assertSame(AssignmentStrategy::ByDepartment, $result->strategy);
        $this->assertNull($result->assigneeId, 'Pool task should have no assignee');
        $this->assertNull($result->candidateRoleId);
        $this->assertSame('dept-1', $result->candidateDepartmentId);
    }

    public function testResolveByDepartmentWithSingleCandidateAutoAssigns(): void
    {
        $orgPort = $this->createStub(OrganizationQueryPort::class);
        $orgPort->method('findActiveEmployeeIdsByDepartmentId')->willReturn([
            ['employeeId' => 'emp-2'],
        ]);

        $resolver = new AssignmentResolver($orgPort);
        $result = $resolver->resolve('by_department', 'org-1', departmentId: 'dept-1');

        $this->assertSame(AssignmentStrategy::ByDepartment, $result->strategy);
        $this->assertSame('emp-2', $result->assigneeId, 'Single candidate should be auto-assigned');
        $this->assertNull($result->candidateRoleId);
        $this->assertNull($result->candidateDepartmentId, 'Auto-assigned task should not have candidateDepartmentId');
    }

    public function testResolveByDepartmentWithNullDepartmentIdReturnsNullIds(): void
    {
        $resolver = new AssignmentResolver($this->createStub(OrganizationQueryPort::class));

        $result = $resolver->resolve('by_department', 'org-1', departmentId: null);

        $this->assertSame(AssignmentStrategy::ByDepartment, $result->strategy);
        $this->assertNull($result->assigneeId);
        $this->assertNull($result->candidateRoleId);
        $this->assertNull($result->candidateDepartmentId);
    }
}
