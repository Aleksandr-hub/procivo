<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Domain\Service;

use App\Workflow\Domain\Service\ExpressionEvaluator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ExpressionEvaluatorTest extends TestCase
{
    private LoggerInterface $logger;
    private ExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->evaluator = new ExpressionEvaluator($this->logger);
    }

    #[Test]
    public function itEvaluatesSimpleEquality(): void
    {
        $result = $this->evaluator->evaluate("status == 'approved'", ['status' => 'approved']);

        self::assertTrue($result);
    }

    #[Test]
    public function itEvaluatesNumericComparisons(): void
    {
        self::assertTrue($this->evaluator->evaluate('amount > 1000', ['amount' => 1500]));
        self::assertFalse($this->evaluator->evaluate('amount > 1000', ['amount' => 500]));
    }

    #[Test]
    public function itEvaluatesInOperator(): void
    {
        self::assertTrue($this->evaluator->evaluate("role in ['admin', 'manager']", ['role' => 'admin']));
        self::assertFalse($this->evaluator->evaluate("role in ['admin', 'manager']", ['role' => 'user']));
    }

    #[Test]
    public function itEvaluatesNotInOperator(): void
    {
        self::assertTrue($this->evaluator->evaluate("status not in ['blocked', 'deleted']", ['status' => 'active']));
    }

    #[Test]
    public function itEvaluatesLogicalOperators(): void
    {
        self::assertTrue($this->evaluator->evaluate(
            'approved and amount > 100',
            ['approved' => true, 'amount' => 200],
        ));
    }

    #[Test]
    public function itEvaluatesNullCoalescing(): void
    {
        self::assertSame('pending', $this->evaluator->evaluate("status ?? 'pending'", []));
        self::assertSame('active', $this->evaluator->evaluate("status ?? 'pending'", ['status' => 'active']));
    }

    #[Test]
    public function itReturnsFalseOnUndefinedVariable(): void
    {
        $result = $this->evaluator->evaluate("decision == 'Done'", []);

        self::assertFalse($result);
    }

    #[Test]
    public function itLogsWarningOnUndefinedVariable(): void
    {
        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Expression evaluation failed',
                self::callback(static function (array $context): bool {
                    return isset($context['expression'], $context['error'], $context['error_class'], $context['variable_keys'])
                        && $context['expression'] === "decision == 'Done'"
                        && \is_string($context['error'])
                        && \is_string($context['error_class'])
                        && $context['variable_keys'] === [];
                }),
            );

        $this->evaluator->evaluate("decision == 'Done'", []);
    }

    #[Test]
    public function itReturnsFalseOnTypeMismatch(): void
    {
        // Comparing an array to a string triggers a type error in ExpressionLanguage
        $result = $this->evaluator->evaluate("status == 'active'", ['status' => ['nested' => 'value']]);

        self::assertFalse($result);
    }

    #[Test]
    public function itLogsWarningOnTypeMismatch(): void
    {
        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Expression evaluation failed',
                self::callback(static function (array $context): bool {
                    return isset($context['expression'], $context['error'], $context['error_class'], $context['variable_keys'])
                        && $context['expression'] === "status == 'active'"
                        && \is_string($context['error'])
                        && \is_string($context['error_class'])
                        && $context['variable_keys'] === ['status'];
                }),
            );

        $this->evaluator->evaluate("status == 'active'", ['status' => ['nested' => 'value']]);
    }

    #[Test]
    public function itReturnsFalseOnSyntaxError(): void
    {
        $result = $this->evaluator->evaluate('invalid !! expression', []);

        self::assertFalse($result);
    }

    #[Test]
    public function itEvaluatesNotEqualOperator(): void
    {
        self::assertTrue($this->evaluator->evaluate("status != 'rejected'", ['status' => 'approved']));
    }

    #[Test]
    public function itEvaluatesComparisonOperators(): void
    {
        self::assertTrue($this->evaluator->evaluate('amount >= 100', ['amount' => 100]));
        self::assertTrue($this->evaluator->evaluate('amount >= 100', ['amount' => 150]));
        self::assertFalse($this->evaluator->evaluate('amount >= 100', ['amount' => 99]));

        self::assertTrue($this->evaluator->evaluate('amount <= 100', ['amount' => 100]));
        self::assertTrue($this->evaluator->evaluate('amount <= 100', ['amount' => 50]));
        self::assertFalse($this->evaluator->evaluate('amount <= 100', ['amount' => 101]));
    }
}
