<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

final readonly class TaskWorkflowSummaryDTO implements \JsonSerializable
{
    public function __construct(
        public string $processInstanceId,
        public string $processName,
        public string $nodeName,
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
