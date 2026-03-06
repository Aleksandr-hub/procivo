# API Versioning Strategy

## Overview

Procivo uses **URL-based versioning** for its REST API. All endpoints are prefixed with `/api/v{N}/`, where `{N}` is the major version number.

**Current version:** v1 (1.0.0)

All v1 endpoints are documented in the OpenAPI specification available at `/api/doc.json` (Swagger UI at `/api/docs`).

## Versioning Approach

| Aspect            | Decision                                                                 |
|-------------------|--------------------------------------------------------------------------|
| Strategy          | URL path versioning (`/api/v1/`, `/api/v2/`)                            |
| Location          | Version is part of the URL path, not headers or query parameters         |
| Granularity       | API-wide (all endpoints share the same version prefix)                   |
| Specification     | Each version has its own OpenAPI spec                                    |

### Rationale

URL-based versioning was chosen for the following reasons:

- **Explicit** — the version is visible in every request, reducing ambiguity
- **Cache-friendly** — HTTP caches and CDNs can distinguish versions by URL without inspecting headers
- **Easy to route** — load balancers and reverse proxies can route by URL prefix
- **Simple to test** — version is visible in browser address bar, curl commands, and API clients
- **Framework support** — Symfony routing natively supports path prefixes

## What Constitutes a Breaking Change

The following changes require a new major API version:

- Removing an endpoint or HTTP method
- Removing or renaming a required request field
- Removing or renaming a response field
- Changing a field type (e.g., `string` to `integer`)
- Changing authentication requirements for an endpoint
- Changing error response structure

## What Is NOT a Breaking Change

The following changes are backward-compatible and do NOT require a new version:

- Adding new optional request fields
- Adding new response fields
- Adding new endpoints
- Adding new enum values (additive)
- Changing descriptions or documentation text

## v1 to v2 Migration Guide

> **Note:** v2 does not exist yet. This section serves as a forward-looking guide and will be updated when v2 is introduced.

### Parallel Operation

When v2 is introduced, both v1 and v2 will run in parallel for a minimum deprecation period of **6 months**. During this period:

- v1 endpoints will continue to function normally
- v1 responses will include a `Sunset` header ([RFC 8594](https://www.rfc-editor.org/rfc/rfc8594)) indicating the planned removal date
- v2 endpoints will be available under the `/api/v2/` prefix

### Breaking Changes Log

| Endpoint (v1)       | Change Description | Endpoint (v2)       | Migration Steps |
|----------------------|--------------------|----------------------|-----------------|
| *No breaking changes yet* | *v1 is the current and only version* | — | — |

### Migration Steps Template

When breaking changes are introduced, each entry will document:

1. **Endpoint path change** — old path vs new path
2. **Request body changes** — removed/renamed fields, new required fields
3. **Response body changes** — removed/renamed fields, type changes
4. **Before/after examples** — request and response samples for both versions

## Deprecation Process

When a new major version is released, the previous version follows this deprecation lifecycle:

1. **Announce deprecation** — document in changelog and API docs; notify API consumers
2. **Add Sunset header** — v1 responses include `Sunset: <date>` header with the planned removal date
3. **v2 goes live** — new version endpoints available alongside v1
4. **Monitor v1 usage** — track v1 request volume via access logs and metrics
5. **Remove v1** — after the deprecation period ends and usage is minimal, remove v1 endpoints

### Sunset Header Example

```http
HTTP/1.1 200 OK
Content-Type: application/json
Sunset: Sat, 01 Mar 2027 00:00:00 GMT
Link: </api/v2/tasks>; rel="successor-version"
```
