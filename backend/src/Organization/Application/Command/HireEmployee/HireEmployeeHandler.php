<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\HireEmployee;

use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Exception\EmployeeAlreadyExistsException;
use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Exception\PositionNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\EmployeeNumber;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class HireEmployeeHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private DepartmentRepositoryInterface $departmentRepository,
        private PositionRepositoryInterface $positionRepository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(HireEmployeeCommand $command): void
    {
        $organizationId = OrganizationId::fromString($command->organizationId);

        if (null === $this->organizationRepository->findById($organizationId)) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        if (null === $this->departmentRepository->findById(DepartmentId::fromString($command->departmentId))) {
            throw DepartmentNotFoundException::withId($command->departmentId);
        }

        if (null === $this->positionRepository->findById(PositionId::fromString($command->positionId))) {
            throw PositionNotFoundException::withId($command->positionId);
        }

        if ($this->employeeRepository->existsByUserIdAndOrganizationId($command->userId, $organizationId)) {
            throw EmployeeAlreadyExistsException::forUser($command->userId, $command->organizationId);
        }

        $managerId = null !== $command->managerId
            ? EmployeeId::fromString($command->managerId)
            : null;

        $employee = Employee::hire(
            id: EmployeeId::fromString($command->id),
            organizationId: $organizationId,
            userId: $command->userId,
            positionId: PositionId::fromString($command->positionId),
            departmentId: DepartmentId::fromString($command->departmentId),
            employeeNumber: new EmployeeNumber($command->employeeNumber),
            hiredAt: new \DateTimeImmutable($command->hiredAt),
            managerId: $managerId,
        );

        $this->employeeRepository->save($employee);
    }
}
