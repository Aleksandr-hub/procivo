<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DismissEmployee;

use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DismissEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(DismissEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findById(
            EmployeeId::fromString($command->employeeId),
        );

        if (null === $employee) {
            throw EmployeeNotFoundException::withId($command->employeeId);
        }

        $employee->dismiss();

        $this->employeeRepository->save($employee);
    }
}
