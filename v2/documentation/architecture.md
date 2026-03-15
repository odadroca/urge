# URGE v2 Architecture

## System Overview

URGE v2 is a prompt management system built on Laravel 12 + Livewire 3. The UX centers on a single-screen **Workspace** where prompts are authored, versioned, tested, and compared without page navigation.

## Data Model

### Entity Relationship

```
User ──< Prompt ──< PromptVersion ──< Result
              │                          │
              └── Category               └── LlmProvider (nullable)

Collection ──< CollectionItem ──> (PromptVersion | Result)
```

### Tables

| Table | Purpose | Key Fields |
|---|---|---|
| users | Auth + roles | role (admin/editor/viewer) |
| prompts | Prompt/fragment container | slug (unique), type, pinned_version_id, tags (JSON) |
| prompt_versions | Immutable version snapshots | version_number, content, variables (JSON), includes (JSON), variable_metadata (JSON) |
| results | Unified response storage | source (api/manual/import), provider_name, model_name, response_text, starred, rating |
| categories | Prompt categorization | name, slug, color |
| llm_providers | API provider config | driver, api_key (encrypted), model, endpoint |
| collections | Ordered groups (Phase 3) | title, description |
| collection_items | Polymorphic items (Phase 3) | item_type, item_id, sort_order |

### Key Design Decisions

1. **Unified Result model** — v1 had 3 tables (prompt_runs, llm_responses, library_entries) for what is fundamentally one concept: "a response to a prompt version." The `source` column distinguishes origin; the `starred` boolean replaces the Library feature.

2. **Prompt type column** — `prompt` vs `fragment`. Fragments are prompts intended for embedding via `{{>slug}}`. Same model, same versioning, just a type flag.

3. **Pinned version** — `pinned_version_id` on prompts. NULL means "latest version is active." Explicit pin overrides. Replaces v1's `active_version_id` with clearer semantics.

4. **Free-text provider/model** — `provider_name` and `model_name` on results are free text, not FKs. Users paste results from ChatGPT's web UI — they shouldn't need a configured provider for that. `llm_provider_id` is only set for API-driven results.

## Component Architecture

### Screen Layout

```
┌──────────────────────────────────────────────────────────────┐
│ Nav: [URGE] [Dashboard] [Browse] [Settings]    [User] [Logout]│
├──────────────────────────────────────────────────────────────┤
│                         <main>                                │
│  Dashboard: grid of cards                                     │
│  Browse: tabbed list with search                              │
│  Workspace: 3-panel layout (sidebar | editor | results)       │
│  Settings: tabbed forms                                       │
└──────────────────────────────────────────────────────────────┘
```

### Workspace Component Communication

WorkspacePage is the orchestrator. Child components communicate via Livewire events:

```
Editor ──[version-created]──> WorkspacePage
  ├──> VersionSidebar (refresh list, select new version)
  ├──> ResultsPanel (update current version filter)
  └──> ManualResultForm (update current version id)

VersionSidebar ──[version-selected]──> WorkspacePage
  ├──> Editor (load version content)
  ├──> ResultsPanel (filter to selected version)
  └──> ManualResultForm (update current version id)

ManualResultForm ──[result-saved]──> ResultsPanel (refresh)
```

### Service Layer

```
TemplateEngine
  ├── extractVariables(content) → string[]
  ├── extractIncludes(content) → string[]
  └── render(content, variables, metadata?) → {rendered, variables_used, variables_missing, includes_resolved}

VersioningService
  └── createVersion(prompt, data, user) → PromptVersion
      (transactional, auto-numbers, extracts vars/includes, filters metadata)
```

## Phase Roadmap

| Phase | Scope | New Components/Services |
|---|---|---|
| 1 (done) | Core workspace: create, version, paste results, star/rate | Dashboard, Browse, WorkspacePage, Editor, VersionSidebar, ResultsPanel, ManualResultForm, PromptMetadata |
| 2 | Rich editing: autocomplete, visual composer, diff, compare | VisualComposer, CompareModal, VariableMetadata + autocomplete.js, diff.js |
| 3 | Import/export, collections | ImportResults, ImportExportService, CollectionList + migrations |
| 4 | LLM integration, AI features | RunWithLlm, LlmProviders (settings), AiAssistantService + 6 drivers |
| 5 | API layer, v1 migration, polish | API controllers, ApiKeyAuthentication middleware, ImportV1Command |
