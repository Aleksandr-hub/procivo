<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeletePosition;

use App\Organization\Domain\Exception\PositionNotFoundException;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\PositionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeletePositionHandler
{
    public function __construct(
        private PositionRepositoryInterface $positionRepository,
    ) {
    }

    public function __invoke(DeletePositionCommand $command): void
    {
        $position = $this->positionRepository->findById(
            PositionId::fromString($command->positionId),
        );

        if (null === $position) {
            throw PositionNotFoundException::withId($command->positionId);
        }

        $this->positionRepository->remove($position);
    }
}
