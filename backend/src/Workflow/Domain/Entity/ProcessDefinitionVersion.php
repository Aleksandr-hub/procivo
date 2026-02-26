<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionVersionId;

class ProcessDefinitionVersion
{
    private string $id;
    private string $processDefinitionId;
    private int $versionNumber;
    /** @var array<string, mixed> */
    private array $nodesSnapshot;
    private \DateTimeImmutable $publishedAt;
    private string $publishedBy;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $nodesSnapshot
     */
    public static function create(
        ProcessDefinitionVersionId $id,
        ProcessDefinitionId $processDefinitionId,
        int $versionNumber,
        array $nodesSnapshot,
        string $publishedBy,
    ): self {
        $ver = new self();
        $ver->id = $id->value();
        $ver->processDefinitionId = $processDefinitionId->value();
        $ver->versionNumber = $versionNumber;
        $ver->nodesSnapshot = $nodesSnapshot;
        $ver->publishedAt = new \DateTimeImmutable();
        $ver->publishedBy = $publishedBy;

        return $ver;
    }

    public function id(): ProcessDefinitionVersionId
    {
        return ProcessDefinitionVersionId::fromString($this->id);
    }

    public function processDefinitionId(): ProcessDefinitionId
    {
        return ProcessDefinitionId::fromString($this->processDefinitionId);
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function nodesSnapshot(): array
    {
        return $this->nodesSnapshot;
    }

    public function publishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function publishedBy(): string
    {
        return $this->publishedBy;
    }
}
