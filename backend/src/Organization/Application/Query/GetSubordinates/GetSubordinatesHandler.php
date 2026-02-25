<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetSubordinates;

use App\Organization\Application\DTO\EmployeeDTO;
use App\Organization\Domain\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSubordinatesHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    /**
     * @return list<EmployeeDTO>
     */
    public function __invoke(GetSubordinatesQuery $query): array
    {
        $employeeId = EmployeeId::fromString($query->employeeId);

        $employee = $this->employeeRepository->findById($employeeId);
        if (null === $employee) {
            throw EmployeeNotFoundException::withId($employeeId->value());
        }

        if (!$query->recursive) {
            $subordinates = $this->employeeRepository->findByManagerId($employeeId);

            return array_map(EmployeeDTO::fromEntity(...), $subordinates);
        }

        $result = [];
        $queue = [$employeeId];

        while ([] !== $queue) {
            $currentId = array_shift($queue);
            $subordinates = $this->employeeRepository->findByManagerId($currentId);

            foreach ($subordinates as $sub) {
                $result[] = EmployeeDTO::fromEntity($sub);
                $queue[] = $sub->id();
            }
        }

        return $result;
    }
}
