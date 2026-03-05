<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

final readonly class ProcessBoardInstanceDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $startedAt,
        public ?string $activeNodeId,
        public ?string $activeNodeName,
        public ?string $activeTaskId,
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

final readonly class ProcessBoardMetricsDTO implements \JsonSerializable
{
    /**
     * @param list<array{date: string, count: int}> $completedByDay
     */
    public function __construct(
        public int $totalActive,
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

final readonly class ProcessBoardDataDTO implements \JsonSerializable
{
    /**
     * @param list<ProcessBoardInstanceDTO> $instances
     */
    public function __construct(
        public array $instances,
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
