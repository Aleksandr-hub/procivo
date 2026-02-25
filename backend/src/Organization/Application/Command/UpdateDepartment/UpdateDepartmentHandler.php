<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateDepartment;

use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateDepartmentHandler
{
    public function __construct(
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    public function __invoke(UpdateDepartmentCommand $command): void
    {
        $department = $this->departmentRepository->findById(
            DepartmentId::fromString($command->departmentId),
        );

        if (null === $department) {
            throw DepartmentNotFoundException::withId($command->departmentId);
        }

        $department->update($command->name, $command->description, $command->sortOrder);

        $this->departmentRepository->save($department);
    }
}
