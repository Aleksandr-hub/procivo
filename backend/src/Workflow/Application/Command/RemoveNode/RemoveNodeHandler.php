<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\RemoveNode;

use App\Workflow\Domain\Repository\NodeRepositoryInterface;
use App\Workflow\Domain\ValueObject\NodeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RemoveNodeHandler
{
    public function __construct(
        private NodeRepositoryInterface $nodeRepository,
    ) {
    }

    public function __invoke(RemoveNodeCommand $command): void
    {
        $node = $this->nodeRepository->findById(NodeId::fromString($command->nodeId));

        if (null === $node) {
            throw new \DomainException(\sprintf('Node with ID "%s" not found.', $command->nodeId));
        }

        $this->nodeRepository->remove($node);
    }
}
