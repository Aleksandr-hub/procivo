<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\RemoveLabel;

use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\ValueObject\LabelId;
use App\TaskManager\Domain\ValueObject\TaskId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RemoveLabelHandler
{
    public function __construct(
        private LabelRepositoryInterface $labelRepository,
    ) {
    }

    public function __invoke(RemoveLabelCommand $command): void
    {
        $this->labelRepository->removeFromTask(
            LabelId::fromString($command->labelId),
            TaskId::fromString($command->taskId),
        );
    }
}
