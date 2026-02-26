<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\AddTransition;

use App\Shared\Application\Command\CommandInterface;

final readonly class AddTransitionCommand implements CommandInterface
{
    /**
     * @param array<int, array<string, mixed>>|null $formFields
     */
    public function __construct(
        public string $id,
        public string $processDefinitionId,
        public string $sourceNodeId,
        public string $targetNodeId,
        public ?string $name = null,
        public ?string $actionKey = null,
        public ?string $conditionExpression = null,
        public ?array $formFields = null,
        public int $sortOrder = 0,
    ) {
    }
}
