<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListPositions;

use App\Organization\Application\DTO\PositionDTO;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListPositionsHandler
{
    public function __construct(
        private PositionRepositoryInterface $positionRepository,
    ) {
    }

    /**
     * @return list<PositionDTO>
     */
    public function __invoke(ListPositionsQuery $query): array
    {
        $positions = null !== $query->departmentId
            ? $this->positionRepository->findByDepartmentId(DepartmentId::fromString($query->departmentId))
            : $this->positionRepository->findByOrganizationId(OrganizationId::fromString($query->organizationId));

        return array_map(
            static fn ($pos) => PositionDTO::fromEntity($pos),
            $positions,
        );
    }
}
