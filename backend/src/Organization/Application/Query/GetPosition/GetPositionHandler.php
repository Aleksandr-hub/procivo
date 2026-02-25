<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetPosition;

use App\Organization\Application\DTO\PositionDTO;
use App\Organization\Domain\Exception\PositionNotFoundException;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\PositionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetPositionHandler
{
    public function __construct(
        private PositionRepositoryInterface $positionRepository,
    ) {
    }

    public function __invoke(GetPositionQuery $query): PositionDTO
    {
        $position = $this->positionRepository->findById(
            PositionId::fromString($query->positionId),
        );

        if (null === $position) {
            throw PositionNotFoundException::withId($query->positionId);
        }

        return PositionDTO::fromEntity($position);
    }
}
