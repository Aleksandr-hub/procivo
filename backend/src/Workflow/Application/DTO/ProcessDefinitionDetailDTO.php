<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Process definition with full graph (nodes and transitions)')]
final readonly class ProcessDefinitionDetailDTO implements \JsonSerializable
{
    /**
     * @param list<NodeDTO>       $nodes
     * @param list<TransitionDTO> $transitions
     */
    public function __construct(
        #[OA\Property(description: 'Process definition')]
        public ProcessDefinitionDTO $definition,
        #[OA\Property(description: 'Workflow nodes', type: 'array', items: new OA\Items(ref: new \Nelmio\ApiDocBundle\Attribute\Model(type: NodeDTO::class)))]
        public array $nodes,
        #[OA\Property(description: 'Workflow transitions', type: 'array', items: new OA\Items(ref: new \Nelmio\ApiDocBundle\Attribute\Model(type: TransitionDTO::class)))]
        public array $transitions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ...$this->definition->jsonSerialize(),
            'nodes' => array_map(static fn (NodeDTO $n) => $n->jsonSerialize(), $this->nodes),
            'transitions' => array_map(static fn (TransitionDTO $t) => $t->jsonSerialize(), $this->transitions),
        ];
    }
}
