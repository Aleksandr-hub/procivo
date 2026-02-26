<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\AddTransition;

use App\Workflow\Domain\Entity\Transition;
use App\Workflow\Domain\Repository\TransitionRepositoryInterface;
use App\Workflow\Domain\ValueObject\ConditionExpression;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\TransitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AddTransitionHandler
{
    public function __construct(
        private TransitionRepositoryInterface $transitionRepository,
    ) {
    }

    public function __invoke(AddTransitionCommand $command): void
    {
        $transition = Transition::create(
            id: TransitionId::fromString($command->id),
            processDefinitionId: ProcessDefinitionId::fromString($command->processDefinitionId),
            sourceNodeId: NodeId::fromString($command->sourceNodeId),
            targetNodeId: NodeId::fromString($command->targetNodeId),
            name: $command->name,
            actionKey: $command->actionKey,
            conditionExpression: null !== $command->conditionExpression
                ? ConditionExpression::fromString($command->conditionExpression)
                : null,
            formFields: $command->formFields,
            sortOrder: $command->sortOrder,
        );

        $this->transitionRepository->save($transition);
    }
}
