<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

final readonly class ProcessInstanceDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $variables
     * @param array<string, array<string, mixed>> $tokens
     */
    public function __construct(
        public string $id,
        public string $definitionId,
        public string $definitionName,
        public string $versionId,
        public string $organizationId,
        public string $status,
        public string $startedBy,
        public array $variables,
        public array $tokens,
        public string $startedAt,
        public ?string $completedAt,
        public ?string $cancelledAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        /** @var array<string, array<string, mixed>> $tokens */
        $tokens = \is_string($row['tokens']) ? json_decode($row['tokens'], true, 512, \JSON_THROW_ON_ERROR) : ($row['tokens'] ?? []);
        /** @var array<string, mixed> $variables */
        $variables = \is_string($row['variables']) ? json_decode($row['variables'], true, 512, \JSON_THROW_ON_ERROR) : ($row['variables'] ?? []);

        return new self(
            id: (string) $row['id'],
            definitionId: (string) $row['definition_id'],
            definitionName: (string) $row['definition_name'],
            versionId: (string) $row['version_id'],
            organizationId: (string) $row['organization_id'],
            status: (string) $row['status'],
            startedBy: (string) $row['started_by'],
            variables: $variables,
            tokens: $tokens,
            startedAt: (string) $row['started_at'],
            completedAt: isset($row['completed_at']) ? (string) $row['completed_at'] : null,
            cancelledAt: isset($row['cancelled_at']) ? (string) $row['cancelled_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'definition_id' => $this->definitionId,
            'definition_name' => $this->definitionName,
            'version_id' => $this->versionId,
            'organization_id' => $this->organizationId,
            'status' => $this->status,
            'started_by' => $this->startedBy,
            'variables' => $this->variables,
            'tokens' => array_values($this->tokens),
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'cancelled_at' => $this->cancelledAt,
        ];
    }
}
