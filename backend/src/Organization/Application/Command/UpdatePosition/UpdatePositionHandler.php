<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdatePosition;

use App\Organization\Domain\Exception\PositionNotFoundException;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Domain\ValueObject\PositionName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdatePositionHandler
{
    public function __construct(
        private PositionRepositoryInterface $positionRepository,
    ) {
    }

    public function __invoke(UpdatePositionCommand $command): void
    {
        $position = $this->positionRepository->findById(
            PositionId::fromString($command->positionId),
        );

        if (null === $position) {
            throw PositionNotFoundException::withId($command->positionId);
        }

        $position->update(
            new PositionName($command->name),
            $command->description,
            $command->sortOrder,
            $command->isHead,
        );

        $this->positionRepository->save($position);
    }
}
