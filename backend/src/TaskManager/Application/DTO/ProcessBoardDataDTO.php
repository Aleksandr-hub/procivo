<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Process board instance summary')]
final readonly class ProcessBoardInstanceDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Process instance UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Process instance display name')]
        public string $name,
        #[OA\Property(description: 'Instance status', enum: ['running', 'completed', 'cancelled'])]
        public string $status,
        #[OA\Property(description: 'Start timestamp', format: 'date-time')]
        public string $startedAt,
        #[OA\Property(description: 'Currently active workflow node UUID', format: 'uuid', nullable: true)]
        public ?string $activeNodeId,
        #[OA\Property(description: 'Currently active workflow node name', nullable: true)]
        public ?string $activeNodeName,
        #[OA\Property(description: 'Active task UUID at current node', format: 'uuid', nullable: true)]
        public ?string $activeTaskId,
        #[OA\Property(description: 'Active task assignee name', nullable: true)]
        public ?string $activeTaskAssigneeName,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

#[OA\Schema(description: 'Process board aggregate metrics')]
final readonly class ProcessBoardMetricsDTO implements \JsonSerializable
{
    /**
     * @param list<array{date: string, count: int}> $completedByDay
     */
    public function __construct(
        #[OA\Property(description: 'Total active (running) instances')]
        public int $totalActive,
        #[OA\Property(description: 'Completed instances per day', type: 'array', items: new OA\Items(
            properties: [
                new OA\Property(property: 'date', type: 'string', format: 'date'),
                new OA\Property(property: 'count', type: 'integer'),
            ],
            type: 'object',
        ))]
        public array $completedByDay,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

#[OA\Schema(description: 'Process board data with instances and metrics')]
final readonly class ProcessBoardDataDTO implements \JsonSerializable
{
    /**
     * @param list<ProcessBoardInstanceDTO> $instances
     */
    public function __construct(
        #[OA\Property(description: 'Process instances', type: 'array', items: new OA\Items(ref: new \Nelmio\ApiDocBundle\Attribute\Model(type: ProcessBoardInstanceDTO::class)))]
        public array $instances,
        #[OA\Property(description: 'Aggregate metrics')]
        public ProcessBoardMetricsDTO $metrics,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
