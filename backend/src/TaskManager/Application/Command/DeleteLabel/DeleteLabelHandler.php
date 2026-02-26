<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteLabel;

use App\TaskManager\Domain\Exception\LabelNotFoundException;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\ValueObject\LabelId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteLabelHandler
{
    public function __construct(
        private LabelRepositoryInterface $labelRepository,
    ) {
    }

    public function __invoke(DeleteLabelCommand $command): void
    {
        $label = $this->labelRepository->findById(LabelId::fromString($command->labelId));

        if (null === $label) {
            throw LabelNotFoundException::withId($command->labelId);
        }

        $this->labelRepository->remove($label);
    }
}
