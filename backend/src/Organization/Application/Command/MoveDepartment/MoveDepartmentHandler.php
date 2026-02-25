<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\MoveDepartment;

use App\Organization\Domain\Exception\DepartmentCircularReferenceException;
use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class MoveDepartmentHandler
{
    public function __construct(
        private DepartmentRepositoryInterface $departmentRepository,
    ) {
    }

    public function __invoke(MoveDepartmentCommand $command): void
    {
        $departmentId = DepartmentId::fromString($command->departmentId);
        $department = $this->departmentRepository->findById($departmentId);

        if (null === $department) {
            throw DepartmentNotFoundException::withId($command->departmentId);
        }

        $oldLevel = $department->level();
        $oldPath = $department->path();

        $newParentId = null;
        $newLevel = 0;
        $newPath = DepartmentPath::root()->append($departmentId);

        if (null !== $command->newParentId) {
            $newParentId = DepartmentId::fromString($command->newParentId);
            $newParent = $this->departmentRepository->findById($newParentId);

            if (null === $newParent) {
                throw DepartmentNotFoundException::withId($command->newParentId);
            }

            if ($newParent->path()->contains($departmentId)) {
                throw DepartmentCircularReferenceException::forDepartment($command->departmentId);
            }

            $newLevel = $newParent->level() + 1;
            $newPath = $newParent->path()->append($departmentId);
        }

        $department->moveTo($newParentId, $newLevel, $newPath);
        $this->departmentRepository->save($department);

        $levelDiff = $newLevel - $oldLevel;
        $descendants = $this->departmentRepository->findDescendants($departmentId);

        foreach ($descendants as $descendant) {
            $updatedPathValue = str_replace(
                $oldPath->value(),
                $newPath->value(),
                $descendant->path()->value(),
            );
            $descendant->updatePath(
                $descendant->level() + $levelDiff,
                new DepartmentPath($updatedPathValue),
            );
            $this->departmentRepository->save($descendant);
        }
    }
}
