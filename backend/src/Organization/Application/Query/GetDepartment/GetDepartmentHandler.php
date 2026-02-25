<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetDepartment;

use App\Organization\Application\DTO\DepartmentDTO;
use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDepartmentHandler
{
    public function __construct(
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    public function __invoke(GetDepartmentQuery $query): DepartmentDTO
    {
        $department = $this->departmentRepository->findById(
            DepartmentId::fromString($query->departmentId),
        );

        if (null === $department) {
            throw DepartmentNotFoundException::withId($query->departmentId);
        }

        return DepartmentDTO::fromEntity($department);
    }
}
