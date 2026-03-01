---
phase: 01-backend-foundation
plan: 03
subsystem: workflow
tags: [expression-lint, graph-validation, xor-gateway, design-time-validation]

# Dependency graph
requires: [01-01]
provides:
  - "ExpressionEvaluator.lint() for design-time syntax validation"
  - "ProcessGraphValidator expression syntax checking on publish"
  - "Hardened XOR gateway error message with node ID"
  - "9 new tests (5 lint + 4 graph validator)"
affects: [publish-workflow, designer-validation]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Design-time lint via Parser::IGNORE_UNKNOWN_VARIABLES — validates syntax without requiring runtime variables"
    - "ProcessGraphValidator validates expressions on all transitions at publish time"

key-files:
  created:
    - backend/tests/Unit/Workflow/Domain/Service/ProcessGraphValidatorTest.php
  modified:
    - backend/src/Workflow/Domain/Service/ExpressionEvaluator.php
    - backend/src/Workflow/Domain/Service/ProcessGraphValidator.php
    - backend/src/Workflow/Domain/Service/WorkflowEngine.php
    - backend/tests/Unit/Workflow/Domain/Service/ExpressionEvaluatorTest.php

key-decisions:
  - "ExpressionEvaluator.lint() uses Symfony ExpressionLanguage native lint with IGNORE_UNKNOWN_VARIABLES flag"
  - "ProcessGraphValidator gets ExpressionEvaluator via constructor injection (autowired)"
  - "Expression validation runs after structural validation in ProcessGraphValidator"

patterns-established:
  - "Design-time validation separate from runtime evaluation"
  - "ProcessGraphValidator extensible via private validation methods called from validate()"

requirements-completed: [GATE-04]

# Metrics
duration: 5min
completed: 2026-02-28
---

# Phase 1 Plan 3: Expression Lint & Gateway Hardening Summary

**Design-time expression syntax validation via lint(), ProcessGraphValidator expression checking, hardened XOR gateway error messages**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-28
- **Completed:** 2026-02-28
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- ExpressionEvaluator.lint() validates expression syntax at design time using Parser::IGNORE_UNKNOWN_VARIABLES
- ProcessGraphValidator validates condition_expression syntax on all transitions when publishing
- Invalid expressions block publishing with descriptive error (transition name + syntax error details)
- XOR gateway error message includes node ID and notes missing default branch
- 5 new lint tests + 4 new ProcessGraphValidator tests, all passing
- Full backend test suite green (133 tests, 315 assertions, 0 regressions)

## Task Commits

1. **Task 1: lint() + ProcessGraphValidator + XOR gateway** - `52d5a63` (feat)
2. **Task 2: DI wiring verification** - No changes needed (autowiring handles all)

## Files Created/Modified
- `backend/tests/Unit/Workflow/Domain/Service/ProcessGraphValidatorTest.php` — 4 tests: valid graph, valid expressions, invalid expression, empty expressions
- `backend/src/Workflow/Domain/Service/ExpressionEvaluator.php` — Added lint() method + SyntaxError/Parser imports
- `backend/src/Workflow/Domain/Service/ProcessGraphValidator.php` — Added ExpressionEvaluator injection + validateExpressionSyntax()
- `backend/src/Workflow/Domain/Service/WorkflowEngine.php` — Enhanced XOR gateway error message with node ID
- `backend/tests/Unit/Workflow/Domain/Service/ExpressionEvaluatorTest.php` — Added 5 lint tests

## Decisions Made
- DI wiring via Symfony autowiring — no explicit service config needed
- Pre-existing PHPStan errors in WorkflowEngine.php (mixed types from array access) left as-is — not introduced by this plan

## Deviations from Plan
None

## Issues Encountered
None

## Self-Check: PASSED

All key files verified on disk. Commit verified in git history.

---
*Phase: 01-backend-foundation*
*Completed: 2026-02-28*
