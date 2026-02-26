<?php

declare(strict_types=1);

namespace App\Workflow\Infrastructure\Repository;

use App\Workflow\Domain\Entity\Node;
use App\Workflow\Domain\Repository\NodeRepositoryInterface;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineNodeRepository implements NodeRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Node $node): void
    {
        $this->entityManager->persist($node);
        $this->entityManager->flush();
    }

    public function remove(Node $node): void
    {
        $this->entityManager->remove($node);
        $this->entityManager->flush();
    }

    public function findById(NodeId $id): ?Node
    {
        return $this->entityManager->find(Node::class, $id->value());
    }

    /**
     * @return list<Node>
     */
    public function findByProcessDefinitionId(ProcessDefinitionId $processDefinitionId): array
    {
        return $this->entityManager->getRepository(Node::class)->findBy([
            'processDefinitionId' => $processDefinitionId->value(),
        ]);
    }
}
