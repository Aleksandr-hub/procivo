<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\ProcessDefinitionVersion;

final readonly class ProcessDefinitionVersionDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public int $versionNumber,
        public string $publishedAt,
        public string $publishedBy,
        public int $runningInstanceCount = 0,
    ) {
    }

    public static function fromEntity(ProcessDefinitionVersion $version, int $runningInstanceCount = 0): self
    {
        return new self(
            id: $version->id()->value(),
            versionNumber: $version->versionNumber(),
            publishedAt: $version->publishedAt()->format(\DateTimeInterface::ATOM),
            publishedBy: $version->publishedBy(),
            runningInstanceCount: $runningInstanceCount,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'version_number' => $this->versionNumber,
            'published_at' => $this->publishedAt,
            'published_by' => $this->publishedBy,
            'running_instance_count' => $this->runningInstanceCount,
        ];
    }
}
