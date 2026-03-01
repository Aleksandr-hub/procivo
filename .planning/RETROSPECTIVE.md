# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 — Workflow + Tasks Integration

**Shipped:** 2026-03-01
**Phases:** 5 | **Plans:** 14 | **Sessions:** ~5

### What Was Built
- Full BPM execution loop: design processes in visual editor → publish → start → fill task forms → choose actions → XOR gateway routing → complete
- Backend: ExpressionEvaluator, FormSchemaBuilder, FormSchemaValidator, AssignmentResolver, claim/unclaim with pessimistic locking
- Frontend: ActionFormDialog with Zod validation, ProcessContextCard, MyPathStepper, PoolTaskBanner, full-page task list
- Designer: assignment strategy selector, per-transition form field builder, canvas validation warnings

### What Worked
- Bottom-up phase ordering (backend → API → frontend → designer) — each phase delivered a verifiable capability that unblocked the next
- GSD workflow with plan-check and verifier agents — caught issues before execution
- TDD approach in backend phases — unit tests confirmed behavior before integration
- Parallel plan execution within phases (where dependencies allowed)
- Figma Make prototype as design reference — adapted to PrimeVue without pixel-perfect matching

### What Was Inefficient
- Phase 4 missing VERIFICATION.md — process gap that flagged 11 requirements as "unsatisfied" in audit despite working code
- GATE-04 checkbox staleness — requirement was implemented and verified but checkbox never updated
- Phase 4 Plan 05 took 25min (longest) — build verification + visual checkpoint was time-consuming
- formSchema snapshot written to DB but frontend reads live schema from workflow context — wasted work on snapshot write path

### Patterns Established
- Forms per ACTION (transition) — architectural pattern for BPMN task forms
- Variable namespacing by node ID with flat aliases — prevents key collisions in multi-stage processes
- Custom domain validation (not Symfony Validator) for dynamic JSON schemas
- definition-changed signal pattern for designer → page communication
- hasSubmitted pattern for deferred blur validation in Vue forms
- Real entities via ::create() in tests instead of stubs for verifying actual domain behavior

### Key Lessons
1. Always create VERIFICATION.md for every phase — missing verification files cause audit false negatives
2. Update requirement checkboxes immediately when code is verified, not as a batch later
3. formSchema snapshot vs live read path should be decided up front — don't implement both
4. Design-time expression lint via Parser::IGNORE_UNKNOWN_VARIABLES is a clean pattern for syntax-only validation
5. Pessimistic locking (PESSIMISTIC_WRITE) is the right choice for claim/unclaim concurrency, not optimistic locking

### Cost Observations
- Model mix: ~60% opus, ~30% sonnet, ~10% haiku (balanced profile)
- Sessions: ~5 (2 days of work)
- Notable: 14 plans in 74min total execution time (~5min avg per plan)

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Sessions | Phases | Key Change |
|-----------|----------|--------|------------|
| v1.0 | ~5 | 5 | First GSD milestone — established plan-check + verifier workflow |

### Cumulative Quality

| Milestone | Tests | Coverage | Key Metric |
|-----------|-------|----------|------------|
| v1.0 | 38+ unit tests | N/A | 14/14 plans complete, 33/34 reqs checked |

### Top Lessons (Verified Across Milestones)

1. Always create VERIFICATION.md for every phase — prevents audit false negatives
2. Bottom-up phase ordering works well for module integration milestones
