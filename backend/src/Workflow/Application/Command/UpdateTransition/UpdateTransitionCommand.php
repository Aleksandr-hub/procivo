<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\UpdateTransition;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateTransitionCommand implements CommandInterface
{
    /**
     * @param array<int, array<string, mixed>>|null $formFields
     */
    public function __construct(
        public string $transitionId,
        public ?string $name,
        public ?string $actionKey,
        public ?string $conditionExpression,
        public ?array $formFields,
        public int $sortOrder,
    ) {
    }
}
