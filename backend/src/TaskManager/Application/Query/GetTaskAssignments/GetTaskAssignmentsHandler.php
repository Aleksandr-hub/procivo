<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\GetTaskAssignments;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\TaskManager\Application\DTO\TaskAssignmentDTO;
use App\TaskManager\Domain\Repository\TaskAssignmentRepositoryInterface;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTaskAssignmentsHandler
{
    public function __construct(
        private TaskAssignmentRepositoryInterface $assignmentRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return list<TaskAssignmentDTO>
     */
    public function __invoke(GetTaskAssignmentsQuery $query): array
    {
        $assignments = $this->assignmentRepository->findByTaskId(TaskId::fromString($query->taskId));

        if (empty($assignments)) {
            return [];
        }

        // Collect employee IDs and build name lookup
        $employeeIds = array_unique(array_map(fn ($a) => $a->employeeId(), $assignments));
        $nameMap = [];

        foreach ($employeeIds as $empId) {
            $employee = $this->employeeRepository->findById(EmployeeId::fromString($empId));
            if (null !== $employee) {
                $user = $this->userRepository->findById(UserId::fromString($employee->userId()));
                if (null !== $user) {
                    $nameMap[$empId] = $user->firstName() . ' ' . $user->lastName();
                }
            }
        }

        return array_map(
            fn ($a) => TaskAssignmentDTO::fromEntity($a, $nameMap[$a->employeeId()] ?? null),
            $assignments,
        );
    }
}
