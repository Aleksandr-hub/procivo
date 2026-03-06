---
phase: 16-product-documentation
plan: 01
subsystem: ui
tags: [markdown, minisearch, vite-plugin, help-center, knowledge-base]

requires:
  - phase: 14.1-ui-refresh-figma-design-system-adaptation
    provides: DashboardLayout, AppSidebar, AppTopbar, PrimeVue Aura theme
provides:
  - Help Center page at /help with category navigation and search
  - Article rendering page at /help/:slug with breadcrumb and markdown display
  - Custom Vite markdown plugin for .md file transformation
  - MiniSearch full-text search composable with fuzzy matching
  - Help module types (ArticleMeta, HelpArticle, HelpCategory)
  - Build-time article indexing via import.meta.glob
  - Sample article proving end-to-end pipeline
affects: [16-02-PLAN, 16-03-PLAN]

tech-stack:
  added: [markdown-it, minisearch, markdown-it-anchor]
  patterns: [custom-vite-plugin, build-time-glob-indexing, composable-search]

key-files:
  created:
    - frontend/plugins/vite-plugin-markdown.ts
    - frontend/src/modules/help/types/help.types.ts
    - frontend/src/modules/help/data/articles.ts
    - frontend/src/modules/help/composables/useHelpSearch.ts
    - frontend/src/modules/help/components/HelpSearchBar.vue
    - frontend/src/modules/help/components/HelpArticleRenderer.vue
    - frontend/src/modules/help/components/HelpCategoryCard.vue
    - frontend/src/modules/help/pages/HelpCenterPage.vue
    - frontend/src/modules/help/pages/HelpArticlePage.vue
    - docs/knowledge-base/user-guide/getting-started/01-login-and-registration.md
  modified:
    - frontend/vite.config.ts
    - frontend/package.json
    - frontend/tsconfig.node.json
    - frontend/src/router/index.ts
    - frontend/src/shared/components/AppSidebar.vue
    - frontend/src/shared/components/AppTopbar.vue
    - frontend/src/i18n/locales/uk.json
    - frontend/src/i18n/locales/en.json

key-decisions:
  - "Custom Vite plugin instead of vite-plugin-markdown: the npm package is outdated (markdown-it v12, domhandler v4), incompatible with Vite 7"
  - "@docs alias in Vite config to resolve repo-root docs/ directory from frontend/ project root"
  - "MiniSearch with boost weights: title x3, keywords x2, description x1.5, fuzzy 0.2, prefix search enabled"
  - "Articles indexed at build time via import.meta.glob eager mode for zero-runtime overhead"

patterns-established:
  - "Markdown article schema: YAML frontmatter with module, feature, roles, category, subcategory, order, keywords, related, lastUpdated"
  - "Custom Vite plugin pattern: plugins/ directory included in tsconfig.node.json"
  - "Help module structure: types, data, composables, components, pages"

requirements-completed: [PDOC-03, PDOC-04]

duration: 6min
completed: 2026-03-06
---

# Phase 16 Plan 01: Help Center Infrastructure Summary

**Custom Vite markdown plugin with MiniSearch full-text search, Help Center page with category navigation, and article rendering pipeline**

## Performance

- **Duration:** 6 min
- **Started:** 2026-03-06T18:03:08Z
- **Completed:** 2026-03-06T18:09:16Z
- **Tasks:** 2
- **Files modified:** 18

## Accomplishments
- Built custom Vite plugin for markdown files with frontmatter parsing and markdown-it rendering (replaces outdated vite-plugin-markdown)
- Help Center page at /help with category cards, subcategory groups, and article listing
- Full-text search via MiniSearch with fuzzy matching, prefix search, and weighted fields
- Article page at /help/:slug+ with breadcrumb navigation, rendered markdown, related articles, and 404 state
- Sidebar "Dovídka" link and complete i18n coverage in uk.json and en.json

## Task Commits

Each task was committed atomically:

1. **Task 1: Install dependencies, configure Vite, create Help module data layer** - `c6efc32` (feat)
2. **Task 2: Build Help Center pages, router, sidebar link, i18n** - `832cfed` (feat)

## Files Created/Modified
- `frontend/plugins/vite-plugin-markdown.ts` - Custom Vite plugin: .md to JS module with frontmatter, body, html
- `frontend/src/modules/help/types/help.types.ts` - ArticleMeta, HelpArticle, HelpCategory, HelpModule types
- `frontend/src/modules/help/types/markdown.d.ts` - TypeScript declaration for .md module imports
- `frontend/src/modules/help/data/articles.ts` - Build-time article index via import.meta.glob, category grouping
- `frontend/src/modules/help/composables/useHelpSearch.ts` - MiniSearch composable with search(), query, results
- `frontend/src/modules/help/components/HelpSearchBar.vue` - Debounced search input with dropdown results
- `frontend/src/modules/help/components/HelpArticleRenderer.vue` - v-html markdown renderer with scoped CSS
- `frontend/src/modules/help/components/HelpCategoryCard.vue` - Category card with icon, label, count
- `frontend/src/modules/help/pages/HelpCenterPage.vue` - Help Center landing with categories and article listing
- `frontend/src/modules/help/pages/HelpArticlePage.vue` - Article page with breadcrumb, meta tags, related articles
- `docs/knowledge-base/user-guide/getting-started/01-login-and-registration.md` - Sample article with full frontmatter
- `frontend/vite.config.ts` - Added markdown plugin and @docs alias
- `frontend/tsconfig.node.json` - Added plugins/**/*.ts to include
- `frontend/src/router/index.ts` - Added /help and /help/:slug+ routes
- `frontend/src/shared/components/AppSidebar.vue` - Added Help link at bottom of sidebar
- `frontend/src/shared/components/AppTopbar.vue` - Added help-center, help-article to route-to-i18n mapping
- `frontend/src/i18n/locales/uk.json` - Added help section with categories, subcategories, search i18n
- `frontend/src/i18n/locales/en.json` - Added help section with English translations

## Decisions Made
- Used custom Vite plugin instead of vite-plugin-markdown npm package: the package depends on markdown-it v12 and domhandler v4, which are incompatible with the project's Vite 7 setup
- Added @docs Vite alias to resolve docs/ from the repo root since Vite root is frontend/
- Article slug extracted by stripping everything up to "knowledge-base/" for robustness regardless of alias resolution path

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Custom Vite plugin instead of vite-plugin-markdown**
- **Found during:** Task 1 (dependency installation)
- **Issue:** vite-plugin-markdown@2.2.0 depends on markdown-it@^12, domhandler@^4, htmlparser2@^6 -- all outdated and potentially incompatible with Vite 7
- **Fix:** Created custom Vite plugin at frontend/plugins/vite-plugin-markdown.ts using markdown-it and markdown-it-anchor directly
- **Files modified:** frontend/plugins/vite-plugin-markdown.ts, frontend/vite.config.ts, frontend/tsconfig.node.json
- **Verification:** TypeScript compiles, Vite build succeeds, markdown files correctly transformed
- **Committed in:** c6efc32 (Task 1 commit)

**2. [Rule 3 - Blocking] Added @docs Vite alias for cross-directory glob**
- **Found during:** Task 1 (data layer)
- **Issue:** import.meta.glob('/docs/knowledge-base/**/*.md') would resolve relative to frontend/ project root, but docs/ lives at repo root
- **Fix:** Added @docs alias in vite.config.ts pointing to ../docs, used in glob pattern
- **Files modified:** frontend/vite.config.ts, frontend/src/modules/help/data/articles.ts
- **Verification:** Build succeeds, sample article included in bundle
- **Committed in:** c6efc32 (Task 1 commit)

---

**Total deviations:** 2 auto-fixed (2 blocking)
**Impact on plan:** Both auto-fixes necessary for the build pipeline to work. No scope creep.

## Issues Encountered
None beyond the deviations documented above.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Help Center infrastructure complete, ready for Plans 02-03 to add articles
- Article frontmatter schema established: title, description, module, feature, roles, category, subcategory, order, keywords, related, lastUpdated
- Search indexing automatic: any .md file added to docs/knowledge-base/ is picked up at build time

## Self-Check: PASSED

All 10 created files verified on disk. Both task commits (c6efc32, 832cfed) verified in git log.

---
*Phase: 16-product-documentation*
*Completed: 2026-03-06*
