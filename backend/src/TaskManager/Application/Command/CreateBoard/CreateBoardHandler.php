<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateBoard;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\TaskManager\Domain\Entity\Board;
use App\TaskManager\Domain\Repository\BoardRepositoryInterface;
use App\TaskManager\Domain\ValueObject\BoardId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateBoardHandler
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository,
    ) {
    }

    public function __invoke(CreateBoardCommand $command): void
    {
        $board = Board::create(
            id: BoardId::fromString($command->id),
            organizationId: OrganizationId::fromString($command->organizationId),
            name: $command->name,
            description: $command->description,
        );

        $this->boardRepository->save($board);
    }
}
