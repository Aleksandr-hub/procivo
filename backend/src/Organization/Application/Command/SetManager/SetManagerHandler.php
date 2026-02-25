<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SetManager;

use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SetManagerHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(SetManagerCommand $command): void
    {
        $employee = $this->employeeRepository->findById(
            EmployeeId::fromString($command->employeeId),
        );

        if (null === $employee) {
            throw EmployeeNotFoundException::withId($command->employeeId);
        }

        $managerId = null;
        if (null !== $command->managerId) {
            $manager = $this->employeeRepository->findById(
                EmployeeId::fromString($command->managerId),
            );

            if (null === $manager) {
                throw EmployeeNotFoundException::withId($command->managerId);
            }

            if (!$employee->organizationId()->equals($manager->organizationId())) {
                throw new DomainException('Manager must belong to the same organization.');
            }

            $managerId = $manager->id();
        }

        $employee->setManager($managerId);

        $this->employeeRepository->save($employee);
    }
}
