<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExpressionEvaluator
{
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function evaluate(string $expression, array $variables = []): mixed
    {
        try {
            return $this->expressionLanguage->evaluate($expression, $variables);
        } catch (\Throwable $e) {
            $this->logger->warning('Expression evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage(),
                'error_class' => $e::class,
                'variable_keys' => array_keys($variables),
            ]);

            return false;
        }
    }
}
