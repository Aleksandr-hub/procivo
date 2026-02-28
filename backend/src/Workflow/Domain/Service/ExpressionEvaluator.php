<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Parser;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class ExpressionEvaluator
{
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * Validate expression syntax at design time.
     * Uses IGNORE_UNKNOWN_VARIABLES since runtime variables are unknown at design time.
     *
     * @throws SyntaxError if expression is syntactically invalid
     */
    public function lint(string $expression): void
    {
        $this->expressionLanguage->lint(
            $expression,
            [],
            Parser::IGNORE_UNKNOWN_VARIABLES,
        );
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
