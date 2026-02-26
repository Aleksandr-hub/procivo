<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEmployee;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Application\DTO\EmployeeDTO;
use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private DepartmentRepositoryInterface $departmentRepository,
        private PositionRepositoryInterface $positionRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetEmployeeQuery $query): EmployeeDTO
    {
        $employee = $this->employeeRepository->findById(
            EmployeeId::fromString($query->employeeId),
        );

        if (null === $employee) {
            throw EmployeeNotFoundException::withId($query->employeeId);
        }

        $dept = $this->departmentRepository->findById($employee->departmentId());
        $pos = $this->positionRepository->findById($employee->positionId());
        $user = $this->userRepository->findById(UserId::fromString($employee->userId()));

        return EmployeeDTO::fromEntity(
            $employee,
            departmentName: $dept?->name(),
            positionName: $pos?->name()->value(),
            userFullName: null !== $user ? $user->firstName() . ' ' . $user->lastName() : null,
            userEmail: $user?->email()->value(),
        );
    }
}
