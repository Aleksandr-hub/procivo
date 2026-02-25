<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateDepartment;

use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\Exception\DepartmentCodeAlreadyExistsException;
use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateDepartmentHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    public function __invoke(CreateDepartmentCommand $command): void
    {
        $organizationId = OrganizationId::fromString($command->organizationId);
        $organization = $this->organizationRepository->findById($organizationId);

        if (null === $organization) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        $code = new DepartmentCode($command->code);

        if ($this->departmentRepository->existsByCode($code, $organizationId)) {
            throw DepartmentCodeAlreadyExistsException::withCode($command->code, $command->organizationId);
        }

        $departmentId = DepartmentId::fromString($command->id);
        $level = 0;
        $path = DepartmentPath::root()->append($departmentId);

        if (null !== $command->parentId) {
            $parentId = DepartmentId::fromString($command->parentId);
            $parent = $this->departmentRepository->findById($parentId);

            if (null === $parent) {
                throw DepartmentNotFoundException::withId($command->parentId);
            }

            $level = $parent->level() + 1;
            $path = $parent->path()->append($departmentId);
        }

        $department = Department::create(
            id: $departmentId,
            organizationId: $organizationId,
            parentId: null !== $command->parentId ? DepartmentId::fromString($command->parentId) : null,
            name: $command->name,
            code: $code,
            description: $command->description,
            sortOrder: $command->sortOrder,
            level: $level,
            path: $path,
        );

        $this->departmentRepository->save($department);
    }
}
