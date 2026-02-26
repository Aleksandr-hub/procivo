<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

final readonly class TaskWorkflowContextDTO implements \JsonSerializable
{
    /**
     * @param array{shared_fields: list<array<string, mixed>>, actions: list<array<string, mixed>>} $formSchema
     */
    public function __construct(
        public string $processInstanceId,
        public string $processName,
        public string $nodeName,
        public string $nodeId,
        public bool $isCompleted,
        public array $formSchema,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'process_instance_id' => $this->processInstanceId,
            'process_name' => $this->processName,
            'node_name' => $this->nodeName,
            'node_id' => $this->nodeId,
            'is_completed' => $this->isCompleted,
            'form_schema' => $this->formSchema,
        ];
    }
}
