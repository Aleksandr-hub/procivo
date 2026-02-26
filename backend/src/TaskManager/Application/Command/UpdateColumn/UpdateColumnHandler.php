<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateColumn;

use App\TaskManager\Domain\Exception\BoardColumnNotFoundException;
use App\TaskManager\Domain\Repository\BoardColumnRepositoryInterface;
use App\TaskManager\Domain\ValueObject\ColumnId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateColumnHandler
{
    public function __construct(
        private BoardColumnRepositoryInterface $columnRepository,
    ) {
    }

    public function __invoke(UpdateColumnCommand $command): void
    {
        $column = $this->columnRepository->findById(ColumnId::fromString($command->columnId));

        if (null === $column) {
            throw BoardColumnNotFoundException::withId($command->columnId);
        }

        $column->update(
            name: $command->name,
            position: $command->position,
            statusMapping: $command->statusMapping,
            wipLimit: $command->wipLimit,
            color: $command->color,
        );

        $this->columnRepository->save($column);
    }
}
