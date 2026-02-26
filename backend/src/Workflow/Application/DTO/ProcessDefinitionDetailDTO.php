<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

final readonly class ProcessDefinitionDetailDTO implements \JsonSerializable
{
    /**
     * @param list<NodeDTO>       $nodes
     * @param list<TransitionDTO> $transitions
     */
    public function __construct(
        public ProcessDefinitionDTO $definition,
        public array $nodes,
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
