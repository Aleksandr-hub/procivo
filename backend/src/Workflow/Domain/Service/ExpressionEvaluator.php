<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExpressionEvaluator
{
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function evaluate(string $expression, array $variables = []): mixed
    {
        return $this->expressionLanguage->evaluate($expression, $variables);
    }
}
