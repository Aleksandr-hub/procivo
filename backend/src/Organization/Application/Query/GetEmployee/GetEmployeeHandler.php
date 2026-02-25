<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEmployee;

use App\Organization\Application\DTO\EmployeeDTO;
use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
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

        return EmployeeDTO::fromEntity($employee);
    }
}
