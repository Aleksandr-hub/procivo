<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\AddNode;

use App\Workflow\Domain\Entity\Node;
use App\Workflow\Domain\Repository\NodeRepositoryInterface;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\NodeType;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AddNodeHandler
{
    public function __construct(
        private NodeRepositoryInterface $nodeRepository,
    ) {
    }

    public function __invoke(AddNodeCommand $command): void
    {
        $node = Node::create(
            id: NodeId::fromString($command->id),
            processDefinitionId: ProcessDefinitionId::fromString($command->processDefinitionId),
            type: NodeType::from($command->type),
            name: $command->name,
            description: $command->description,
            config: $command->config,
            positionX: $command->positionX,
            positionY: $command->positionY,
        );

        $this->nodeRepository->save($node);
    }
}
