<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeleteDepartment;

use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteDepartmentHandler
{
    public function __construct(
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    public function __invoke(DeleteDepartmentCommand $command): void
    {
        $departmentId = DepartmentId::fromString($command->departmentId);
        $department = $this->departmentRepository->findById($departmentId);

        if (null === $department) {
            throw DepartmentNotFoundException::withId($command->departmentId);
        }

        $descendants = $this->departmentRepository->findDescendants($departmentId);

        if ([] !== $descendants) {
            throw new DomainException('Cannot delete department that has child departments.');
        }

        $this->departmentRepository->remove($department);
    }
}
