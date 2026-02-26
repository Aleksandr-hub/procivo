<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

use App\Workflow\Domain\Entity\Node;
use App\Workflow\Domain\Entity\Transition;
use App\Workflow\Domain\ValueObject\NodeType;

final class ProcessGraphValidator
{
    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     */
    public function validate(array $nodes, array $transitions): ValidationResult
    {
        if (0 === \count($nodes)) {
            return ValidationResult::failure(['Process graph must have at least one node.']);
        }

        $errors = [];

        $this->validateStartNodes($nodes, $errors);
        $this->validateEndNodes($nodes, $errors);
        $this->validateTransitionReferences($nodes, $transitions, $errors);
        $this->validateNoSelfReferences($transitions, $errors);
        $this->validateStartHasNoIncoming($nodes, $transitions, $errors);
        $this->validateEndHasNoOutgoing($nodes, $transitions, $errors);
        $this->validateNoOrphanNodes($nodes, $transitions, $errors);
        $this->validateGatewayConnections($nodes, $transitions, $errors);
        $this->validateReachability($nodes, $transitions, $errors);

        return 0 === \count($errors)
            ? ValidationResult::success()
            : ValidationResult::failure($errors);
    }

    /**
     * @param list<Node>    $nodes
     * @param list<string> &$errors
     */
    private function validateStartNodes(array $nodes, array &$errors): void
    {
        $startNodes = array_filter($nodes, static fn (Node $n) => NodeType::Start === $n->type());

        if (0 === \count($startNodes)) {
            $errors[] = 'Process graph must have exactly one Start node.';
        } elseif (\count($startNodes) > 1) {
            $errors[] = \sprintf('Process graph must have exactly one Start node, found %d.', \count($startNodes));
        }
    }

    /**
     * @param list<Node>    $nodes
     * @param list<string> &$errors
     */
    private function validateEndNodes(array $nodes, array &$errors): void
    {
        $endNodes = array_filter($nodes, static fn (Node $n) => NodeType::End === $n->type());

        if (0 === \count($endNodes)) {
            $errors[] = 'Process graph must have at least one End node.';
        }
    }

    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateTransitionReferences(array $nodes, array $transitions, array &$errors): void
    {
        $nodeIds = array_map(static fn (Node $n) => $n->id()->value(), $nodes);
        $nodeIdSet = array_flip($nodeIds);

        foreach ($transitions as $transition) {
            if (!isset($nodeIdSet[$transition->sourceNodeId()->value()])) {
                $errors[] = \sprintf('Transition "%s" references non-existent source node "%s".', $transition->id()->value(), $transition->sourceNodeId()->value());
            }
            if (!isset($nodeIdSet[$transition->targetNodeId()->value()])) {
                $errors[] = \sprintf('Transition "%s" references non-existent target node "%s".', $transition->id()->value(), $transition->targetNodeId()->value());
            }
        }
    }

    /**
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateNoSelfReferences(array $transitions, array &$errors): void
    {
        foreach ($transitions as $transition) {
            if ($transition->sourceNodeId()->equals($transition->targetNodeId())) {
                $errors[] = \sprintf('Transition "%s" is a self-reference (source equals target).', $transition->id()->value());
            }
        }
    }

    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateStartHasNoIncoming(array $nodes, array $transitions, array &$errors): void
    {
        $startNodes = array_filter($nodes, static fn (Node $n) => NodeType::Start === $n->type());

        foreach ($startNodes as $startNode) {
            foreach ($transitions as $transition) {
                if ($transition->targetNodeId()->equals($startNode->id())) {
                    $errors[] = 'Start node must not have incoming transitions.';

                    return;
                }
            }
        }
    }

    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateEndHasNoOutgoing(array $nodes, array $transitions, array &$errors): void
    {
        $endNodes = array_filter($nodes, static fn (Node $n) => NodeType::End === $n->type());

        foreach ($endNodes as $endNode) {
            foreach ($transitions as $transition) {
                if ($transition->sourceNodeId()->equals($endNode->id())) {
                    $errors[] = \sprintf('End node "%s" must not have outgoing transitions.', $endNode->name());

                    break;
                }
            }
        }
    }

    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateNoOrphanNodes(array $nodes, array $transitions, array &$errors): void
    {
        foreach ($nodes as $node) {
            if (NodeType::Start === $node->type() || NodeType::End === $node->type()) {
                continue;
            }

            $hasIncoming = false;
            $hasOutgoing = false;

            foreach ($transitions as $transition) {
                if ($transition->targetNodeId()->equals($node->id())) {
                    $hasIncoming = true;
                }
                if ($transition->sourceNodeId()->equals($node->id())) {
                    $hasOutgoing = true;
                }
                if ($hasIncoming && $hasOutgoing) {
                    break;
                }
            }

            if (!$hasIncoming || !$hasOutgoing) {
                $errors[] = \sprintf('Node "%s" is orphaned (missing %s transitions).', $node->name(), !$hasIncoming ? 'incoming' : 'outgoing');
            }
        }
    }

    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateGatewayConnections(array $nodes, array $transitions, array &$errors): void
    {
        foreach ($nodes as $node) {
            if (!\in_array($node->type(), [NodeType::ExclusiveGateway, NodeType::ParallelGateway, NodeType::InclusiveGateway], true)) {
                continue;
            }

            $incoming = array_filter($transitions, static fn (Transition $t) => $t->targetNodeId()->equals($node->id()));
            $outgoing = array_filter($transitions, static fn (Transition $t) => $t->sourceNodeId()->equals($node->id()));

            // A merge gateway (2+ incoming, 1 outgoing) is valid — it converges parallel/exclusive paths.
            // Only require 2+ outgoing when the gateway acts as a split (fewer than 2 incoming).
            $isMerge = \count($incoming) >= 2;

            if (!$isMerge && \count($outgoing) > 0 && \count($outgoing) < 2) {
                $errors[] = \sprintf('Gateway "%s" must have at least 2 outgoing transitions, found %d.', $node->name(), \count($outgoing));
            }
        }
    }

    /**
     * @param list<Node>       $nodes
     * @param list<Transition> $transitions
     * @param list<string>    &$errors
     */
    private function validateReachability(array $nodes, array $transitions, array &$errors): void
    {
        $startNodes = array_filter($nodes, static fn (Node $n) => NodeType::Start === $n->type());
        if (0 === \count($startNodes)) {
            return;
        }

        $startNode = reset($startNodes);

        // Build adjacency list
        /** @var array<string, list<string>> $adjacency */
        $adjacency = [];
        foreach ($nodes as $node) {
            $adjacency[$node->id()->value()] = [];
        }
        foreach ($transitions as $transition) {
            $sourceId = $transition->sourceNodeId()->value();
            if (isset($adjacency[$sourceId])) {
                $adjacency[$sourceId][] = $transition->targetNodeId()->value();
            }
        }

        // BFS from start
        $visited = [];
        $queue = [$startNode->id()->value()];
        $visited[$startNode->id()->value()] = true;

        while (\count($queue) > 0) {
            $current = array_shift($queue);
            foreach ($adjacency[$current] ?? [] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $visited[$neighbor] = true;
                    $queue[] = $neighbor;
                }
            }
        }

        foreach ($nodes as $node) {
            if (!isset($visited[$node->id()->value()])) {
                $errors[] = \sprintf('Node "%s" is not reachable from the Start node.', $node->name());
            }
        }
    }
}
