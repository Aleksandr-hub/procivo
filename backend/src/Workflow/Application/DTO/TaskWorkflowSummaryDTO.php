<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Lightweight workflow summary for task list views')]
final readonly class TaskWorkflowSummaryDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Process instance UUID', format: 'uuid')]
        public string $processInstanceId,
        #[OA\Property(description: 'Process definition name')]
        public string $processName,
        #[OA\Property(description: 'Current workflow node name')]
        public string $nodeName,
        #[OA\Property(description: 'Whether the workflow task is completed')]
        public bool $isCompleted,
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
            'is_completed' => $this->isCompleted,
        ];
    }
}
