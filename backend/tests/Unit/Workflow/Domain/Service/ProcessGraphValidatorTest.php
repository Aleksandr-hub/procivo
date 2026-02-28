<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Domain\Service;

use App\Workflow\Domain\Entity\Node;
use App\Workflow\Domain\Entity\Transition;
use App\Workflow\Domain\Service\ExpressionEvaluator;
use App\Workflow\Domain\Service\ProcessGraphValidator;
use App\Workflow\Domain\ValueObject\ConditionExpression;
use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\NodeType;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\TransitionId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProcessGraphValidatorTest extends TestCase
{
    private ProcessGraphValidator $validator;

    protected function setUp(): void
    {
        $expressionEvaluator = new ExpressionEvaluator($this->createStub(LoggerInterface::class));
        $this->validator = new ProcessGraphValidator($expressionEvaluator);
    }

    private function makeNode(NodeType $type, string $name, ?NodeId $id = null): Node
    {
        return Node::create(
            $id ?? NodeId::generate(),
            ProcessDefinitionId::generate(),
            $type,
            $name,
        );
    }

    private function makeTransition(
        NodeId $sourceNodeId,
        NodeId $targetNodeId,
        ?string $name = null,
        ?ConditionExpression $conditionExpression = null,
    ): Transition {
        return Transition::create(
            TransitionId::generate(),
            ProcessDefinitionId::generate(),
            $sourceNodeId,
            $targetNodeId,
            $name,
            conditionExpression: $conditionExpression,
        );
    }

    #[Test]
    public function itPassesValidGraphWithNoExpressions(): void
    {
        $startId = NodeId::generate();
        $taskId = NodeId::generate();
        $endId = NodeId::generate();

        $nodes = [
            $this->makeNode(NodeType::Start, 'Start', $startId),
            $this->makeNode(NodeType::Task, 'Do Work', $taskId),
            $this->makeNode(NodeType::End, 'End', $endId),
        ];

        $transitions = [
            $this->makeTransition($startId, $taskId, 'start-to-task'),
            $this->makeTransition($taskId, $endId, 'task-to-end'),
        ];

        $result = $this->validator->validate($nodes, $transitions);

        self::assertTrue($result->isValid());
    }

    #[Test]
    public function itPassesValidGraphWithValidExpressions(): void
    {
        $startId = NodeId::generate();
        $gatewayId = NodeId::generate();
        $task1Id = NodeId::generate();
        $task2Id = NodeId::generate();
        $endId = NodeId::generate();

        $nodes = [
            $this->makeNode(NodeType::Start, 'Start', $startId),
            $this->makeNode(NodeType::ExclusiveGateway, 'Decision', $gatewayId),
            $this->makeNode(NodeType::Task, 'Task A', $task1Id),
            $this->makeNode(NodeType::Task, 'Task B', $task2Id),
            $this->makeNode(NodeType::End, 'End', $endId),
        ];

        $transitions = [
            $this->makeTransition($startId, $gatewayId, 'start-to-gw'),
            $this->makeTransition($gatewayId, $task1Id, 'gw-to-a', ConditionExpression::fromString("status == 'approved'")),
            $this->makeTransition($gatewayId, $task2Id, 'gw-to-b', ConditionExpression::fromString("status == 'rejected'")),
            $this->makeTransition($task1Id, $endId, 'a-to-end'),
            $this->makeTransition($task2Id, $endId, 'b-to-end'),
        ];

        $result = $this->validator->validate($nodes, $transitions);

        self::assertTrue($result->isValid());
    }

    #[Test]
    public function itFailsGraphWithInvalidExpression(): void
    {
        $startId = NodeId::generate();
        $gatewayId = NodeId::generate();
        $task1Id = NodeId::generate();
        $task2Id = NodeId::generate();
        $endId = NodeId::generate();

        $nodes = [
            $this->makeNode(NodeType::Start, 'Start', $startId),
            $this->makeNode(NodeType::ExclusiveGateway, 'Decision', $gatewayId),
            $this->makeNode(NodeType::Task, 'Task A', $task1Id),
            $this->makeNode(NodeType::Task, 'Task B', $task2Id),
            $this->makeNode(NodeType::End, 'End', $endId),
        ];

        $transitions = [
            $this->makeTransition($startId, $gatewayId, 'start-to-gw'),
            $this->makeTransition($gatewayId, $task1Id, 'broken-transition', ConditionExpression::fromString('invalid !! expression')),
            $this->makeTransition($gatewayId, $task2Id, 'gw-to-b', ConditionExpression::fromString("status == 'ok'")),
            $this->makeTransition($task1Id, $endId, 'a-to-end'),
            $this->makeTransition($task2Id, $endId, 'b-to-end'),
        ];

        $result = $this->validator->validate($nodes, $transitions);

        self::assertFalse($result->isValid());
        self::assertNotEmpty($result->errors());

        $hasExpressionError = false;
        foreach ($result->errors() as $error) {
            if (str_contains($error, 'broken-transition') && str_contains($error, 'invalid expression syntax')) {
                $hasExpressionError = true;
                break;
            }
        }
        self::assertTrue($hasExpressionError, 'Expected expression syntax error for broken-transition');
    }

    #[Test]
    public function itSkipsEmptyExpressions(): void
    {
        $startId = NodeId::generate();
        $taskId = NodeId::generate();
        $endId = NodeId::generate();

        $nodes = [
            $this->makeNode(NodeType::Start, 'Start', $startId),
            $this->makeNode(NodeType::Task, 'Work', $taskId),
            $this->makeNode(NodeType::End, 'End', $endId),
        ];

        $transitions = [
            $this->makeTransition($startId, $taskId, 'start-to-task'),
            $this->makeTransition($taskId, $endId, 'task-to-end'),
        ];

        $result = $this->validator->validate($nodes, $transitions);

        self::assertTrue($result->isValid());
    }
}
