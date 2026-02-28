<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Domain\Service;

use App\Workflow\Domain\Service\ExpressionEvaluator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ExpressionEvaluatorTest extends TestCase
{
    private function createEvaluatorWithStub(): ExpressionEvaluator
    {
        return new ExpressionEvaluator($this->createStub(LoggerInterface::class));
    }

    /**
     * @return array{LoggerInterface&\PHPUnit\Framework\MockObject\MockObject, ExpressionEvaluator}
     */
    private function createEvaluatorWithMock(): array
    {
        $logger = $this->createMock(LoggerInterface::class);

        return [$logger, new ExpressionEvaluator($logger)];
    }

    #[Test]
    public function itEvaluatesSimpleEquality(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        $result = $evaluator->evaluate("status == 'approved'", ['status' => 'approved']);

        self::assertTrue($result);
    }

    #[Test]
    public function itEvaluatesNumericComparisons(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertTrue($evaluator->evaluate('amount > 1000', ['amount' => 1500]));
        self::assertFalse($evaluator->evaluate('amount > 1000', ['amount' => 500]));
    }

    #[Test]
    public function itEvaluatesInOperator(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertTrue($evaluator->evaluate("role in ['admin', 'manager']", ['role' => 'admin']));
        self::assertFalse($evaluator->evaluate("role in ['admin', 'manager']", ['role' => 'user']));
    }

    #[Test]
    public function itEvaluatesNotInOperator(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertTrue($evaluator->evaluate("status not in ['blocked', 'deleted']", ['status' => 'active']));
    }

    #[Test]
    public function itEvaluatesLogicalOperators(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertTrue($evaluator->evaluate(
            'approved and amount > 100',
            ['approved' => true, 'amount' => 200],
        ));
    }

    #[Test]
    public function itEvaluatesNullCoalescing(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertSame('pending', $evaluator->evaluate("status ?? 'pending'", []));
        self::assertSame('active', $evaluator->evaluate("status ?? 'pending'", ['status' => 'active']));
    }

    #[Test]
    public function itReturnsFalseOnUndefinedVariable(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        $result = $evaluator->evaluate("decision == 'Done'", []);

        self::assertFalse($result);
    }

    #[Test]
    public function itLogsWarningOnUndefinedVariable(): void
    {
        [$logger, $evaluator] = $this->createEvaluatorWithMock();

        $logger->expects(self::once())
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

        $evaluator->evaluate("decision == 'Done'", []);
    }

    #[Test]
    public function itReturnsFalseOnTypeMismatch(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        // Math on a string triggers a TypeError in ExpressionLanguage
        $result = $evaluator->evaluate('amount + 10', ['amount' => 'not_a_number']);

        self::assertFalse($result);
    }

    #[Test]
    public function itLogsWarningOnTypeMismatch(): void
    {
        [$logger, $evaluator] = $this->createEvaluatorWithMock();

        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Expression evaluation failed',
                self::callback(static function (array $context): bool {
                    return isset($context['expression'], $context['error'], $context['error_class'], $context['variable_keys'])
                        && $context['expression'] === 'amount + 10'
                        && \is_string($context['error'])
                        && $context['error_class'] === \TypeError::class
                        && $context['variable_keys'] === ['amount'];
                }),
            );

        $evaluator->evaluate('amount + 10', ['amount' => 'not_a_number']);
    }

    #[Test]
    public function itReturnsFalseOnSyntaxError(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        $result = $evaluator->evaluate('invalid !! expression', []);

        self::assertFalse($result);
    }

    #[Test]
    public function itEvaluatesNotEqualOperator(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertTrue($evaluator->evaluate("status != 'rejected'", ['status' => 'approved']));
    }

    #[Test]
    public function itEvaluatesComparisonOperators(): void
    {
        $evaluator = $this->createEvaluatorWithStub();

        self::assertTrue($evaluator->evaluate('amount >= 100', ['amount' => 100]));
        self::assertTrue($evaluator->evaluate('amount >= 100', ['amount' => 150]));
        self::assertFalse($evaluator->evaluate('amount >= 100', ['amount' => 99]));

        self::assertTrue($evaluator->evaluate('amount <= 100', ['amount' => 100]));
        self::assertTrue($evaluator->evaluate('amount <= 100', ['amount' => 50]));
        self::assertFalse($evaluator->evaluate('amount <= 100', ['amount' => 101]));
    }
}
