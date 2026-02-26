<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\RemoveTransition;

use App\Workflow\Domain\Repository\TransitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\TransitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RemoveTransitionHandler
{
    public function __construct(
        private TransitionRepositoryInterface $transitionRepository,
    ) {
    }

    public function __invoke(RemoveTransitionCommand $command): void
    {
        $transition = $this->transitionRepository->findById(TransitionId::fromString($command->transitionId));

        if (null === $transition) {
            throw new \DomainException(\sprintf('Transition with ID "%s" not found.', $command->transitionId));
        }

        $this->transitionRepository->remove($transition);
    }
}
