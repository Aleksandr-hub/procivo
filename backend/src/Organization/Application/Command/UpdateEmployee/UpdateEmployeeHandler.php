<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateEmployee;

use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Exception\PositionNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\PositionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private PositionRepositoryInterface $positionRepository,
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    public function __invoke(UpdateEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findById(
            EmployeeId::fromString($command->employeeId),
        );

        if (null === $employee) {
            throw EmployeeNotFoundException::withId($command->employeeId);
        }

        $positionId = $employee->positionId();
        $departmentId = $employee->departmentId();

        if (null !== $command->positionId) {
            $positionId = PositionId::fromString($command->positionId);

            if (null === $this->positionRepository->findById($positionId)) {
                throw PositionNotFoundException::withId($command->positionId);
            }
        }

        if (null !== $command->departmentId) {
            $departmentId = DepartmentId::fromString($command->departmentId);

            if (null === $this->departmentRepository->findById($departmentId)) {
                throw DepartmentNotFoundException::withId($command->departmentId);
            }
        }

        $employee->changePosition($positionId, $departmentId);

        $this->employeeRepository->save($employee);
    }
}
