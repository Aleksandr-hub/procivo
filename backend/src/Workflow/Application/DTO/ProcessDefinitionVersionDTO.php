<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use App\Workflow\Domain\Entity\ProcessDefinitionVersion;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Published version of a process definition')]
final readonly class ProcessDefinitionVersionDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Version UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Version number (auto-increment)')]
        public int $versionNumber,
        #[OA\Property(description: 'Publish timestamp', format: 'date-time')]
        public string $publishedAt,
        #[OA\Property(description: 'Publisher user UUID', format: 'uuid')]
        public string $publishedBy,
        #[OA\Property(description: 'Running instances on this version')]
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
