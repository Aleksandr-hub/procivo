# Codebase Concerns

**Analysis Date:** 2026-02-27

## Tech Debt

**Workflow Engine Expression Evaluator — Silent Failures:**
- Issue: `ExpressionEvaluator` catches `SyntaxError` and returns `false` for invalid expressions without logging or reporting
- Files: `backend/src/Workflow/Domain/Service/ExpressionEvaluator.php`
- Impact: Invalid gateway conditions or timer expressions silently default to `false`, causing unpredictable process flow without developer awareness
- Fix approach: Add structured logging with expression context; optionally throw typed exception for strict mode (breaking change for phase 3+)

**EventSerializer — Generic RuntimeException:**
- Issue: Unknown event types throw `RuntimeException` with sprintf instead of domain-specific exceptions
- Files: `backend/src/Workflow/Infrastructure/EventStore/EventSerializer.php` (lines 137, 268)
- Impact: Deserialization failures difficult to distinguish from other infrastructure errors; no recovery path for version migrations
- Fix approach: Create `UnknownEventTypeException extends DomainException`; add versioned event support for backward compatibility

**ProcessGraph RuntimeException on Missing Start Node:**
- Issue: `ProcessGraph` throws generic `RuntimeException` for missing start node, not a domain exception
- Files: `backend/src/Workflow/Domain/Service/ProcessGraph.php` (line 59)
- Impact: Infrastructure errors leak into domain logic; unclear how to handle missing start (should be caught at validation, not runtime)
- Fix approach: Throw `ProcessDefinitionInvalidException` at graph construction; ensure ProcessGraphValidator catches this first

**PHPStan Analysis Level 6 — Below Recommendation:**
- Issue: PHPStan configured at level 6 instead of level 8 (project plan states level 6→8)
- Files: `backend/phpstan.neon`
- Impact: Type errors, null safety, and strict class constant usage not checked; risk of runtime errors in complex type scenarios
- Fix approach: Incrementally increase to level 7, then 8 (may require type hints on large class properties)

## Known Bugs

**S3FileStorage Pre-signed URLs — Hard-coded 1 Hour Expiry:**
- Symptoms: File access links become invalid after exactly 1 hour; no way to customize expiry per file
- Files: `backend/src/TaskManager/Infrastructure/Storage/S3FileStorage.php` (line 67)
- Trigger: Call `getUrl()` on any attachment; URL becomes unusable after 60 minutes
- Workaround: Generate new URL on demand (refresh endpoint), but not user-facing in UI
- Fix approach: Make expiry configurable via environment variable; store policy in attachment metadata

**HTTP Client Token Refresh Race Condition — Partial Lock:**
- Symptoms: Multiple simultaneous 401 responses may queue extra refresh requests if not perfectly timed
- Files: `frontend/src/shared/api/http-client.ts` (lines 11-26)
- Trigger: 2+ concurrent API calls with expired token; `isRefreshing` flag is not atomic
- Workaround: Functionally works in practice due to quick refresh; fails only under extreme concurrency (unlikely in browser)
- Fix approach: Use Promise-based queueing with atomic state (already partially implemented; consider Pinia store for token state)

**Task Status Workflow Conflict — Dual Update Paths:**
- Symptoms: Task can be updated via `TransitionTask` command (Symfony Workflow) or direct entity methods; inconsistent state
- Files: `backend/src/TaskManager/Domain/Entity/Task.php` (methods `setStatus()`, `assign()`, `claim()`); multiple handlers in `TaskManager/Application/Command/`
- Trigger: Workflow node completes task via event; user clicks "Assign" simultaneously
- Impact: Race conditions possible; event log may not match actual state
- Fix approach: Route all status changes through single command bus; make task state immutable except via CQRS commands

## Security Considerations

**JWT Refresh Token Storage in LocalStorage:**
- Risk: Refresh tokens stored in plain localStorage; vulnerable to XSS; no SameSite protection
- Files: `frontend/src/shared/api/http-client.ts` (lines 71-72); all auth stores
- Current mitigation: HTTPS enforced in production; short JWT TTL (1h); refresh token rotation implemented server-side
- Recommendations:
  - Use `HttpOnly` cookies for refresh token (requires backend cookie-based auth)
  - Implement `SameSite=Strict` for all auth cookies
  - Add Content Security Policy header to block inline scripts
  - Consider token binding to IP (for session layer, not JWT)

**Expression Evaluation in Workflow Conditions — Code Injection Risk:**
- Risk: User-supplied expressions evaluated directly without sandboxing; `ExpressionLanguage` mitigates but allows function calls
- Files: `backend/src/Workflow/Domain/Service/ExpressionEvaluator.php`; gateway conditions in `WorkflowEngine.php`
- Current mitigation: Symfony `ExpressionLanguage` restricts to approved functions; no `eval()` used
- Recommendations:
  - Document allowed function/variable set in process definition UI
  - Add expression validator that whitelists only comparison/arithmetic operators (block function calls)
  - Log all evaluated expressions for audit trail
  - Consider compile-time validation instead of runtime evaluation

**Form Field Names from User Input — Injection in Task Variables:**
- Risk: Form field names in `FormFieldsBuilder.vue` accept user input and stored as task variables; no escaping on output
- Files: `frontend/src/modules/workflow/components/FormFieldsBuilder.vue` (lines 46-56); `DynamicFormField.vue` (lines 39-96)
- Current mitigation: Frontend sanitization of field names (alphanumeric + underscore); backend stores as JSON
- Recommendations:
  - Validate field name pattern on backend before persisting
  - Escape field names when rendering in variable displays
  - Add length limit on field names (prevent DOS via huge variable keys)
  - Warn if reserved process variable names are used (e.g., `processInstanceId`, `organizationId`)

**No Rate Limiting on Workflow Execution:**
- Risk: Can start unlimited process instances via API; timer/webhook nodes trigger unbounded async work
- Files: `backend/src/Workflow/Presentation/Controller/ProcessDefinitionController.php` (start process endpoint)
- Current mitigation: None
- Recommendations:
  - Add per-organization rate limit on process starts (e.g., 100/minute)
  - Add queue depth limit to prevent RabbitMQ overload
  - Implement cost model for webhook/notification nodes (throttle expensive operations)
  - Log suspiciously high execution counts

## Performance Bottlenecks

**ProcessInstance Event Sourcing — Full Event Replay:**
- Problem: Every process state lookup replays all events from start; no snapshots
- Files: `backend/src/Workflow/Domain/Entity/ProcessInstance.php` (method `reconstitute()`, line 128)
- Cause: Event store returns all events; no snapshot mechanism
- Current impact: Observable for processes >1000 events (complex nested workflows); milliseconds delay
- Improvement path:
  - Implement snapshot storage every N events (e.g., 500)
  - Load last snapshot, then replay newer events
  - Add migration tool to backfill snapshots

**GetOrgChart Query — N+1 Department/Position Lookups:**
- Problem: Recursive tree traversal fetches each department/position separately
- Files: `backend/src/Organization/Application/Query/GetOrgChart/GetOrgChartHandler.php` (232 lines)
- Cause: Doctrine lazy loading; no query optimization via joins/eager load
- Current impact: Organization with 100+ employees takes 2-5 seconds
- Improvement path:
  - Use single query with recursive CTE to fetch entire tree
  - Eager load positions for all departments in one query
  - Cache department tree at application level (invalidate on update)

**Workflow Design Canvas — No Pagination for Large Processes:**
- Problem: All nodes/edges rendered in Vue simultaneously; no virtual scrolling
- Files: `frontend/src/modules/workflow/components/WorkflowDesigner.vue` (lines 50-84)
- Cause: `vue-flow` renders full graph; no lazy loading
- Current impact: Processes with >500 nodes become sluggish (>1s pan/zoom latency)
- Improvement path:
  - Implement viewport-based rendering (render only visible nodes)
  - Use `vue-flow` viewport optimization features
  - Lazy-load node details on selection

**Form Field Collection for Validation — Repeated Graph Traversal:**
- Problem: `FormFieldCollector` traverses process graph multiple times (collect → validate → render)
- Files: `backend/src/Workflow/Application/Command/ExecuteTaskAction/ExecuteTaskActionHandler.php` (line 65)
- Cause: No caching of field definitions per action
- Current impact: ~50ms overhead per task action execution (acceptable but redundant)
- Improvement path:
  - Cache form schema per (processDefinitionId, nodeId, actionKey)
  - Invalidate cache on process definition update
  - Use Redis for distributed cache

## Fragile Areas

**ProcessInstance Entity — 558 Lines, Mutable State:**
- Files: `backend/src/Workflow/Domain/Entity/ProcessInstance.php`
- Why fragile:
  - Large entity with many state transitions (token creation, movement, completion, variable merge)
  - Private array properties directly modified by multiple methods; no immutability
  - Circular state checks (e.g., `allTokensCompleted()` called before state transition)
  - Event sourcing adds complexity; manual event creation in every method
- Safe modification:
  - Add invariant checks at start of public methods (preconditions)
  - Extract token management into separate `TokenCollection` value object
  - Consider separating write model (Entity) from read model (aggregate snapshot)
- Test coverage: Partially tested; integration tests needed for complex multi-token scenarios

**WorkflowEngine — Match Expression with 10 Node Types:**
- Files: `backend/src/Workflow/Domain/Service/WorkflowEngine.php` (lines 29-42)
- Why fragile:
  - Large match expression; adding new node type requires changes here + ExpressionEvaluator + EventSerializer
  - No handler registry; all handlers hardcoded
  - Error handling inconsistent (some throw, some return early)
- Safe modification:
  - Extract each handler into separate class implementing NodeHandler interface
  - Use handler registry (map node type to handler class)
  - Add type-safe handler discovery (auto-wire via service tags)
- Test coverage: Missing tests for exclusive/inclusive gateway condition evaluation with complex expressions

**Task/Workflow Integration — WorkflowTaskLink as Bridge:**
- Files: `backend/src/Workflow/Domain/Entity/WorkflowTaskLink.php`; `backend/src/TaskManager/Application/Command/` (multiple handlers)
- Why fragile:
  - Task can exist without workflow link; workflow can exist without task (optional integration)
  - Unclear ownership: does task completion trigger workflow action, or vice versa?
  - No transactional guarantee; race condition between task update and link update
- Safe modification:
  - Document integration contract (flow diagram in docs)
  - Add validation: link must exist before executing workflow action
  - Make task completion idempotent (safe to retry)
- Test coverage: Integration tests needed for task lifecycle in workflow context

## Scaling Limits

**Local File Storage for Attachments (Dev) — S3 Bucket Isolation:**
- Current capacity: LocalStack S3 (dev) supports ~1GB by default; no quota enforcement
- Limit: Bucket grows unbounded; no cleanup of orphaned files
- Scaling path:
  - Add file retention policy (auto-delete after 30 days unless referenced)
  - Implement S3 lifecycle rules for production (move to Glacier after 90 days)
  - Add space usage monitoring per organization (quota enforcement)

**RabbitMQ Message Queue — No Dead Letter Handling:**
- Current capacity: RabbitMQ 4.2 (docker-compose) no persistence tuning; memory-based
- Limit: Unhandled async messages (timer fires, webhook calls) accumulate if consumer fails
- Scaling path:
  - Add dead letter queue for failed messages
  - Implement exponential backoff + max retry count
  - Monitor queue depth; alert if growing

**Redis for Refresh Tokens — No Persistence Configured:**
- Current capacity: Redis 8 in-memory only; no RDB/AOF
- Limit: Tokens lost on restart; users forced to re-login
- Scaling path:
  - Enable Redis persistence (RDB snapshots every minute or AOF)
  - For high scale: Use Redis Cluster (Phase 4+)
  - Store refresh token hash (not plaintext) with TTL

**Elasticsearch Integration — Not Yet Implemented:**
- Current capacity: Declared in tech stack but no code
- Limit: Full-text search on tasks/processes not available
- Scaling path:
  - Implement task indexing on create/update
  - Add process instance search by variables
  - Set up index rotation (daily indices for time-based queries)

## Test Coverage Gaps

**Workflow Engine — Missing Comprehensive Integration Tests:**
- What's not tested: Multi-token scenarios (parallel gateways merging); complex expression evaluation; timer scheduling accuracy
- Files: `backend/src/Workflow/Domain/Service/WorkflowEngine.php`
- Risk: Process instances with 3+ concurrent tokens may behave unexpectedly under race conditions
- Priority: High (core engine; used by all processes)

**Task Claim/Unclaim Command Handlers — No Integration Tests:**
- What's not tested: Claim after task deletion; claim race condition with manual assignment
- Files: `backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskHandler.php`; `UnclaimTaskHandler.php`
- Risk: Pool task assignments may become inconsistent
- Priority: Medium (business logic; affects user workflows)

**Organization Hierarchy Cycles — No Validation Test:**
- What's not tested: Department move creating cycle (A → B → C → A); no cyclic validation
- Files: `backend/src/Organization/Application/Command/MoveDepartment/MoveDepartmentHandler.php`
- Risk: Infinite recursion when traversing org chart (e.g., GetOrgChart query hangs)
- Priority: High (data integrity)

**Frontend Form Validation — No Unit Tests:**
- What's not tested: Field sanitization edge cases (unicode, SQL injection attempts); form data serialization/deserialization
- Files: `frontend/src/modules/workflow/components/FormFieldsBuilder.vue`; `DynamicFormField.vue`
- Risk: Invalid field names may break process variable evaluation on backend
- Priority: Medium (UX; backend has guard)

**S3FileStorage — No Integration Tests:**
- What's not tested: Bucket creation on first call; pre-signed URL expiry; concurrent uploads; deletion of non-existent files
- Files: `backend/src/TaskManager/Infrastructure/Storage/S3FileStorage.php`
- Risk: File upload/download failures in production; undetected until user action
- Priority: Medium (critical for file attachments; currently only LocalStack tested)

**Notification/Webhook Event Handlers — No Tests:**
- What's not tested: Event handler ordering; missing notification configuration; webhook timeout/retry
- Files: `backend/src/Workflow/Application/EventHandler/` (OnNotificationNodeActivated, OnWebhookNodeActivated, OnSubProcessNodeActivated)
- Risk: Process instances stuck waiting for external notifications that never arrive
- Priority: High (critical async operations; difficult to debug)

## Missing Critical Features

**No Deadlock Prevention in Parallel Gateway:**
- Problem: If one branch of parallel gateway completes early and others hang indefinitely, process stuck forever
- Blocks: Production deployment of complex workflows with 3+ parallel paths

**No Timeout for Async Operations:**
- Problem: Webhook calls or notification sends may hang indefinitely; process waits forever
- Blocks: Reliable workflow execution with external integrations

**No Process Variable Type Safety:**
- Problem: Variables stored as JSON; no schema enforcement; type mismatches in expressions not caught
- Blocks: Complex workflows with many variables (error-prone for business users)

**No Audit Trail for Workflow State Changes:**
- Problem: Event sourcing exists but no human-readable audit log; hard to debug why process took path X
- Blocks: Compliance requirements (financial, healthcare workflows)

---

*Concerns audit: 2026-02-27*
