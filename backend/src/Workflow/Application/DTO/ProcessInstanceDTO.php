<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Running or completed process instance')]
final readonly class ProcessInstanceDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed>                $variables
     * @param array<string, array<string, mixed>> $tokens
     */
    public function __construct(
        #[OA\Property(description: 'Process instance UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Process definition UUID', format: 'uuid')]
        public string $definitionId,
        #[OA\Property(description: 'Process definition name')]
        public string $definitionName,
        #[OA\Property(description: 'Definition version UUID', format: 'uuid')]
        public string $versionId,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Instance status', enum: ['running', 'completed', 'cancelled'])]
        public string $status,
        #[OA\Property(description: 'Starter user UUID', format: 'uuid')]
        public string $startedBy,
        #[OA\Property(description: 'Process variables', type: 'object')]
        public array $variables,
        #[OA\Property(description: 'Execution tokens', type: 'object')]
        public array $tokens,
        #[OA\Property(description: 'Start timestamp', format: 'date-time')]
        public string $startedAt,
        #[OA\Property(description: 'Completion timestamp', format: 'date-time', nullable: true)]
        public ?string $completedAt,
        #[OA\Property(description: 'Cancellation timestamp', format: 'date-time', nullable: true)]
        public ?string $cancelledAt,
    ) {
    }

    /**
     * @param array<string, mixed>  $row
     * @param array<string, string> $timerFireAtMap token_id => fire_at for pending timers
     */
    public static function fromRow(array $row, array $timerFireAtMap = []): self
    {
        /** @var array<string, array<string, mixed>> $tokens */
        $tokens = \is_string($row['tokens']) ? json_decode($row['tokens'], true, 512, \JSON_THROW_ON_ERROR) : ($row['tokens'] ?? []);
        /** @var array<string, mixed> $variables */
        $variables = \is_string($row['variables']) ? json_decode($row['variables'], true, 512, \JSON_THROW_ON_ERROR) : ($row['variables'] ?? []);

        foreach ($tokens as $tokenId => &$token) {
            $token['fire_at'] = $timerFireAtMap[$tokenId] ?? null;
        }
        unset($token);

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
