# Roadmap: Procivo — BPM Platform

## Milestones

- ✅ **v1.0 Workflow + Tasks Integration** — Phases 1-5 (shipped 2026-03-01)
- ✅ **v2.0 Production-Ready BPM** — Phases 6-16 (shipped 2026-03-06)
- 📋 **v3.0 Business Core** — Advanced Workflow, Approvals, Directories, Documents, Business Rules, Templates
- 📋 **v4.0 Integrations + Analytics** — Ukrainian integrations, Reports, Search, Chat, Calendar
- 📋 **v5.0 AI + Platform** — AI Assistant, Module toggling, Import/Export, Entity Passport, PWA
- 📋 **v6.0 Enterprise & Scale** — SSO, Self-hosted, Billing, Compliance, GraphQL, Microservices

## Phases

<details>
<summary>✅ v1.0 Workflow + Tasks Integration (Phases 1-5) — SHIPPED 2026-03-01</summary>

- [x] Phase 1: Backend Foundation (3/3 plans) — completed 2026-02-28
- [x] Phase 2: Form Schema and Assignment (2/2 plans) — completed 2026-02-28
- [x] Phase 3: Completion and Claim APIs (2/2 plans) — completed 2026-02-28
- [x] Phase 4: Frontend Task Integration (5/5 plans) — completed 2026-02-28
- [x] Phase 5: Designer Configuration (2/2 plans) — completed 2026-03-01

Full details: [milestones/v1.0-ROADMAP.md](milestones/v1.0-ROADMAP.md)

</details>

<details>
<summary>✅ v2.0 Production-Ready BPM (Phases 6-16) — SHIPPED 2026-03-06</summary>

- [x] Phase 6: Process Polish (4/4 plans) — completed 2026-03-01
- [x] Phase 6.1: Process Definition Versioning (2/2 plans) — completed 2026-03-01
- [x] Phase 7: User Profile + CI/CD (3/3 plans) — completed 2026-03-01
- [x] Phase 8: Audit Logging (2/2 plans) — completed 2026-03-01
- [x] Phase 9: Notification System (2/2 plans) — completed 2026-03-01
- [x] Phase 10: Dashboard (3/3 plans) — completed 2026-03-05
- [x] Phase 10.1: Board Evolution (4/4 plans) — completed 2026-03-05
- [x] Phase 11: Timer Execution (3/3 plans) — completed 2026-03-05
- [x] Phase 11.1: Board Drag-to-Complete Fix (1/1 plan) — completed 2026-03-05
- [x] Phase 11.2: Process Polish Gap Closure (1/1 plan) — completed 2026-03-05
- [x] Phase 11.3: Avatar Display Extension (2/2 plans) — completed 2026-03-05
- [x] Phase 12: Super Admin Impersonation (2/2 plans) — completed 2026-03-05
- [x] Phase 13: Granular Permissions RBAC (4/4 plans) — completed 2026-03-06
- [x] Phase 14: Infrastructure & Security (5/5 plans) — completed 2026-03-06
- [x] Phase 14.1: UI Refresh (3/3 plans) — completed 2026-03-06
- [x] Phase 15: API Documentation (4/4 plans) — completed 2026-03-06
- [x] Phase 16: Product Documentation (3/3 plans) — completed 2026-03-06

Full details: [milestones/v2.0-ROADMAP.md](milestones/v2.0-ROADMAP.md)

</details>

## Future Milestones (high-level, detailed planning when we get there)

> Reprioritized 2026-03-06 based on BPM market analysis (Creatio, Prozorro, Monday.com, Bitrix24, Odoo).
> See `.planning/research/BPM-MARKET-ANALYSIS.md` for full analysis.
> Key insight: infrastructure is strong (RBAC, audit, versioning, monitoring), but core business features
> (AND gateway, approvals, directories, integrations) must come before AI/enterprise phases.

### v3.0 Business Core

**Milestone Goal:** Complete BPMN 2.0 engine (parallel/inclusive gateways, sub-processes), approval workflows with SLA, universal directory system (Custom Objects), document management, business rules, and ready-made process templates — so the platform can handle real business processes like procurement, HR, and contract approval.

Phases:
- **Phase 17: Advanced Workflow Engine** — Parallel Gateway (AND), Inclusive Gateway (OR), Sub-Process node (nested execution with own start/end), Signal/Message events (inter-process communication), Script Task, multi-instance tasks (sequential/parallel over collection)
- **Phase 18: Approval Workflows + SLA Engine** — Approval node type (sequential/parallel chain, delegation, escalation, substitution), SLA definitions per process/task (deadline + escalation rules), auto-escalation notifications, approval history UI, working-days calendar
- **Phase 19: Directory System (Custom Objects)** — Dynamic catalogs with configurable fields (text, number, date, select, file, relation), hierarchical items, per-directory RBAC, list + detail pages auto-generation, Directory-Workflow integration (directory items as process context, directory_item form field type)
- **Phase 20: Process Files + Document Management** — File attachments at process instance level (not just task), document templates with variable substitution, document approval workflow, version history for documents
- **Phase 21: Conditional Forms + Business Rules** — Conditional field visibility/required based on other field values, field dependency chains, UI-level business rules (show/hide/filter/validate), form field types expansion (rich text, file upload, directory lookup, user picker)
- **Phase 22: Process Templates** — 5-7 ready-made process templates (HR leave request, procurement approval, employee onboarding, contract approval, IT support request, document review), template import/clone, template gallery page

### v4.0 Integrations + Analytics

**Milestone Goal:** Integration framework with external system calls from processes, reporting engine with process analytics, full-text search, Ukrainian B2B connectors, calendar/SLA timeline, and team discussions.

Phases:
- **Phase 23: Integration Framework** — Webhook in/out, "Call Web Service" process node (REST/SOAP from process without code), ConnectorInterface, API keys, OAuth2 client flow, delivery log, retry with backoff
- **Phase 24: Report Builder** — Configurable reports (chart, table, pivot, number card, gauge), process analytics (cycle time, bottleneck detection, throughput), ReportAccess sharing, PDF/Excel export
- **Phase 25: Full-Text Search** — Elasticsearch indexing (tasks, employees, directories, processes), global search bar with Cmd+K, faceted results, RBAC-scoped, search suggestions
- **Phase 26: Ukrainian Business Integrations** — Vchasno EDO (electronic document exchange), Diia.Sign KEP (qualified electronic signature), Nova Poshta (shipment tracking), PrivatBank/Monobank (payment status), OpenDataBot (company registry), Checkbox PRRO (fiscal receipts)
- **Phase 27: Calendar + SLA Timeline** — Calendar view for task deadlines and process milestones, Gantt chart for process instances, SLA violation tracking dashboard, escalation rules configuration
- **Phase 28: Chat & Discussions** — Task/process thread-based chat, @mentions with notification, file sharing in threads, real-time via Mercure

### v5.0 AI + Platform

**Milestone Goal:** AI Assistant for process configuration and data queries, module toggling per organization, import/export with competitor migration, Entity Passport for dynamic detail pages.

Phases:
- **Phase 29: AI Assistant — Read-Only** — Multi-provider (Claude/OpenAI/Gemini), org-scoped context isolation, RBAC-enforced tools, streaming via Mercure, natural language queries over processes/tasks/directories
- **Phase 30: AI Assistant — Write Tools + Modes** — Quick Mode (execute immediately) vs Design Mode (iterative clarification), Preview+Confirm pattern, AI-suggested process templates, usage limits per org
- **Phase 31: Module Toggling & Menu Customization** — Per-org module flags, sidebar config per role, feature flags, custom landing pages
- **Phase 32: Entity Passport** — Dynamic detail pages (PassportTemplate config with universal renderer, tabs, sections, field groups)
- **Phase 33: Import/Export + Migration** — CSV/Excel import with AI column mapping, competitor migration adapters (Creatio, 1C/BAS, Jira, Monday), MigrationWizard, rollback capability
- **Phase 34: Mobile / PWA** — Service worker, responsive design, push notifications, camera integration, QR code scanning

### v6.0 Enterprise & Scale

**Milestone Goal:** Enterprise features for large organizations — SSO, self-hosted packaging, billing, compliance, microservices extraction.

Phases:
- **Phase 35: SSO & Advanced Auth** — SAML 2.0, OIDC, LDAP/AD sync, 2FA enforcement per org, session management, OAuth2 provider
- **Phase 36: Self-Hosted Packaging** — Helm chart, Docker Compose prod template, install wizard, upgrade path, air-gapped support, license key validation
- **Phase 37: Billing & Subscriptions** — Plan tiers (Free/Starter/Pro/Enterprise), per-module pricing, Stripe + LiqPay/Fondy, usage metering, trial period
- **Phase 38: Compliance & Data Protection** — GDPR (consent, right to erasure, data export), data retention policies, DPA template, SAF-T compliance
- **Phase 39: GraphQL API** — Schema for core entities, DataLoader, subscriptions, rate limiting
- **Phase 40: gRPC Inter-service** — Proto definitions, server/client implementation, service mesh prep
- **Phase 41: Microservices Extraction** — Notification + Search as separate services, Traefik gateway, distributed tracing
- **Phase 42: Process Mining & Advanced Analytics** — Process mining from audit log, conformance checking, simulation, DMN decision tables, plugin marketplace

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Backend Foundation | v1.0 | 3/3 | Complete | 2026-02-28 |
| 2. Form Schema and Assignment | v1.0 | 2/2 | Complete | 2026-02-28 |
| 3. Completion and Claim APIs | v1.0 | 2/2 | Complete | 2026-02-28 |
| 4. Frontend Task Integration | v1.0 | 5/5 | Complete | 2026-02-28 |
| 5. Designer Configuration | v1.0 | 2/2 | Complete | 2026-03-01 |
| 6. Process Polish | v2.0 | 4/4 | Complete | 2026-03-01 |
| 6.1 Process Definition Versioning | v2.0 | 2/2 | Complete | 2026-03-01 |
| 7. User Profile + CI/CD | v2.0 | 3/3 | Complete | 2026-03-01 |
| 8. Audit Logging | v2.0 | 2/2 | Complete | 2026-03-01 |
| 9. Notification System | v2.0 | 2/2 | Complete | 2026-03-01 |
| 10. Dashboard | v2.0 | 3/3 | Complete | 2026-03-05 |
| 10.1 Board Evolution | v2.0 | 4/4 | Complete | 2026-03-05 |
| 11. Timer Execution | v2.0 | 3/3 | Complete | 2026-03-05 |
| 11.1 Board Drag-to-Complete Fix | v2.0 | 1/1 | Complete | 2026-03-05 |
| 11.2 Process Polish Gap Closure | v2.0 | 1/1 | Complete | 2026-03-05 |
| 11.3 Avatar Display Extension | v2.0 | 2/2 | Complete | 2026-03-05 |
| 12. Super Admin Impersonation | v2.0 | 2/2 | Complete | 2026-03-05 |
| 13. Granular Permissions (RBAC) | v2.0 | 4/4 | Complete | 2026-03-06 |
| 14. Infrastructure & Security | v2.0 | 5/5 | Complete | 2026-03-06 |
| 14.1 UI Refresh | v2.0 | 3/3 | Complete | 2026-03-06 |
| 15. API Documentation | v2.0 | 4/4 | Complete | 2026-03-06 |
| 16. Product Documentation | v2.0 | 3/3 | Complete | 2026-03-06 |
