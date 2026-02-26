<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\CreateLabel;

use App\TaskManager\Domain\Entity\Label;
use App\TaskManager\Domain\Repository\LabelRepositoryInterface;
use App\TaskManager\Domain\ValueObject\LabelId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateLabelHandler
{
    public function __construct(
        private LabelRepositoryInterface $labelRepository,
    ) {
    }

    public function __invoke(CreateLabelCommand $command): void
    {
        $label = Label::create(
            id: LabelId::fromString($command->id),
            organizationId: $command->organizationId,
            name: $command->name,
            color: $command->color,
        );

        $this->labelRepository->save($label);
    }
}
