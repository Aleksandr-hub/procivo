<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreatePosition;

use App\Organization\Domain\Entity\Position;
use App\Organization\Domain\Exception\DepartmentNotFoundException;
use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Domain\ValueObject\PositionName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreatePositionHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private DepartmentRepositoryInterface $departmentRepository,
        private PositionRepositoryInterface $positionRepository,
    ) {
    }

    public function __invoke(CreatePositionCommand $command): void
    {
        $organizationId = OrganizationId::fromString($command->organizationId);

        if (null === $this->organizationRepository->findById($organizationId)) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        $departmentId = DepartmentId::fromString($command->departmentId);

        if (null === $this->departmentRepository->findById($departmentId)) {
            throw DepartmentNotFoundException::withId($command->departmentId);
        }

        $position = Position::create(
            id: PositionId::fromString($command->id),
            organizationId: $organizationId,
            departmentId: $departmentId,
            name: new PositionName($command->name),
            description: $command->description,
            sortOrder: $command->sortOrder,
            isHead: $command->isHead,
        );

        $this->positionRepository->save($position);
    }
}
