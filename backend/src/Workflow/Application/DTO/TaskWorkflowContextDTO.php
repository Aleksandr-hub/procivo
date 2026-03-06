<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Workflow context for a task including form schema and actions')]
final readonly class TaskWorkflowContextDTO implements \JsonSerializable
{
    /**
     * @param array{shared_fields: list<array<string, mixed>>, actions: list<array<string, mixed>>} $formSchema
     */
    public function __construct(
        #[OA\Property(description: 'Process instance UUID', format: 'uuid')]
        public string $processInstanceId,
        #[OA\Property(description: 'Process definition name')]
        public string $processName,
        #[OA\Property(description: 'Current workflow node name')]
        public string $nodeName,
        #[OA\Property(description: 'Current workflow node UUID', format: 'uuid')]
        public string $nodeId,
        #[OA\Property(description: 'Whether the workflow task is completed')]
        public bool $isCompleted,
        #[OA\Property(description: 'Form schema with shared fields and per-action fields', type: 'object')]
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
