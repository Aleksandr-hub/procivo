# Phase 1: Backend Foundation - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Variable namespacing for process data, XOR gateway condition evaluation against process variables, and form validation infrastructure (FormSchemaValidator). Pure backend — no UI, no frontend changes. Delivers the foundation that Phase 2 (Form Schema) and Phase 3 (Completion API) depend on.

Requirements: GATE-01, GATE-02, GATE-03, GATE-04, COMP-02, COMP-03, COMP-05

</domain>

<decisions>
## Implementation Decisions

### Expression syntax
- Simple comparisons only: ==, !=, >, <, >=, <=, in, not in
- Logical operators: and, or, not
- Null handling: null coalescing, null-safe access
- NO custom functions, NO array operations, NO matches() — keep it simple for designers
- Covers 95% of BPM scenarios (status checks, amount thresholds, role matching)

### Default branch behavior
- Designer can mark one outgoing transition as "default/else" on XOR gateway
- Default branch is NOT required — if no default set and no condition matches, process stops with error
- Validator does NOT enforce default branch at design time (optional but recommended)

### Expression error handling
- Undefined variable → log structured warning, treat condition as false, fall through to default branch
- Type mismatch → same: warning + false
- Process never silently mis-routes — either a condition matches, default is taken, or explicit error
- No process freeze on expression errors — graceful degradation

### Design-time validation
- Syntax check only when saving process definition — verify expression parses without errors
- No semantic validation (variables are unknown at design time)
- Invalid syntax → save blocked with error message pointing to the problematic expression

### Claude's Discretion
- Variable namespace format — how to structure nodeId-based namespacing and how expressions reference namespaced variables
- Validation error format — structure of validation error responses for downstream frontend consumption
- Field dependency model complexity — simple show/hide vs cascading chains
- FormSchemaValidator internal design — class structure, rule registry approach

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 01-backend-foundation*
*Context gathered: 2026-02-28*
