# URGE v2 — Architecture Plan

## Context

URGE v1 works but suffers from too many screens/steps for core workflows, a data model with unnecessary indirection (12 tables where ~7 suffice), and secondary features (LLM API calls) treated as primary. v2 is a fresh start: new repo, redesigned data model, and a UX that minimizes navigation. Hosted on Hostinger shared hosting (PHP only, no Node.js on server; local Node.js for builds is fine).

---

## Stack Decision: Laravel + Livewire 3

**Laravel 12 + Livewire 3 + Alpine.js + Tailwind CSS + SQLite**

The frustration with v1 was never with Laravel — it was with multi-page Blade navigation requiring 5-6 page loads for core workflows. The fix is **Livewire 3**, which turns Laravel into a reactive SPA-like experience without a separate frontend framework or Node.js runtime on the server.

- **Livewire 3**: server-rendered reactive components, SPA-like UX, no Node.js on server
- **Alpine.js**: client-side interactions (drag-and-drop, autocomplete, clipboard)
- **Tailwind CSS**: built locally via Vite, deployed as static assets
- **SQLite**: zero-config, self-contained, same as v1

### Alternatives considered and rejected

| Option | Why rejected |
|---|---|
| Inertia.js + Vue/React | Adds frontend build complexity, no clear benefit over Livewire for this use case |
| Pure SPA + API | Over-engineered for a self-hosted small-team tool, requires Node.js or separate static host |
| Filament | Opinionated admin panel UX — URGE needs a custom workspace, not a CRUD panel |
| Different backend (Python, Node) | Hostinger shared hosting = PHP only. Laravel is the best PHP framework |

---

## Data Model (7 domain tables, down from 12)

### What changed and why

| v1 | v2 | Rationale |
|---|---|---|
| Prompt + separate "includes" concept | **Prompt** with `type: prompt\|fragment` | Fragments (includes) are just prompts meant to be embedded via `{{>slug}}` — unify them |
| `active_version_id` FK on prompts | `pinned_version_id` (nullable) | NULL = latest version is active. Simpler default, explicit pin when needed |
| PromptRun + LlmResponse + LibraryEntry (3 tables) | **Result** (1 table) | A result IS the record of a run. `source` column (api/manual/import), `starred` boolean replaces the Library concept entirely |
| Story + StoryStep | **Collection + CollectionItem** | Polymorphic items (can reference a version or result), more flexible narrative building |
| PromptEnvironment | Dropped | Minimal benefit for self-hosted tool. Re-add as JSON column later if needed |

### Table schemas

#### prompts
```
id                  BIGINT PRIMARY KEY
name                VARCHAR(255) NOT NULL
slug                VARCHAR(255) UNIQUE NOT NULL
description         TEXT NULLABLE
type                VARCHAR(20) DEFAULT 'prompt'   -- 'prompt' | 'fragment'
category_id         BIGINT NULLABLE FK -> categories
tags                JSON NULLABLE
pinned_version_id   BIGINT NULLABLE FK -> prompt_versions  -- NULL = latest is active
created_by          BIGINT FK -> users
deleted_at          TIMESTAMP NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### prompt_versions (immutable)
```
id                  BIGINT PRIMARY KEY
prompt_id           BIGINT FK -> prompts (CASCADE DELETE)
version_number      UNSIGNED INT
content             LONGTEXT
commit_message      VARCHAR(500) NULLABLE
variables           JSON NULLABLE         -- extracted variable names
variable_metadata   JSON NULLABLE         -- type, default, description per variable
includes            JSON NULLABLE         -- extracted {{>slug}} references
created_by          BIGINT FK -> users
created_at          TIMESTAMP

UNIQUE(prompt_id, version_number)
```

#### results (replaces prompt_runs + llm_responses + library_entries)
```
id                  BIGINT PRIMARY KEY
prompt_id           BIGINT FK -> prompts (CASCADE DELETE)
prompt_version_id   BIGINT FK -> prompt_versions (CASCADE DELETE)
source              VARCHAR(20) NOT NULL  -- 'api' | 'manual' | 'import'
provider_name       VARCHAR(100) NULLABLE -- free text: "OpenAI", "Claude", etc.
model_name          VARCHAR(255) NULLABLE -- free text: "gpt-4o", "claude-3.5-sonnet"
llm_provider_id     BIGINT NULLABLE FK -> llm_providers (only for API calls)
rendered_content    LONGTEXT NULLABLE     -- prompt as sent (variables filled)
variables_used      JSON NULLABLE         -- key-value pairs used in rendering
response_text       LONGTEXT NULLABLE
notes               TEXT NULLABLE
rating              TINYINT NULLABLE      -- 1-5
starred             BOOLEAN DEFAULT FALSE -- replaces the Library concept
input_tokens        UNSIGNED INT NULLABLE
output_tokens       UNSIGNED INT NULLABLE
duration_ms         UNSIGNED INT NULLABLE
status              VARCHAR(20) DEFAULT 'success'  -- 'success' | 'error' | 'pending'
error_message       TEXT NULLABLE
import_filename     VARCHAR(255) NULLABLE -- original .md filename if imported
created_by          BIGINT FK -> users
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### collections (replaces stories)
```
id                  BIGINT PRIMARY KEY
title               VARCHAR(255) NOT NULL
description         TEXT NULLABLE
created_by          BIGINT FK -> users
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### collection_items (replaces story_steps)
```
id                  BIGINT PRIMARY KEY
collection_id       BIGINT FK -> collections (CASCADE DELETE)
sort_order          UNSIGNED INT DEFAULT 0
item_type           VARCHAR(50) NOT NULL  -- 'prompt_version' | 'result'
item_id             BIGINT NOT NULL       -- polymorphic reference
notes               TEXT NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### categories, llm_providers, api_keys, users
Unchanged from v1.

### Key design decisions

1. **Single `results` table** — In v1, the same response text could exist in both `llm_responses` and `library_entries`. v2 treats every response as a first-class entity. The `starred` flag is the equivalent of "save to library" without duplicating data.

2. **`provider_name` and `model_name` as free text** — For manual paste and import, the user types "ChatGPT" or "Claude" as free text without needing a configured provider. The `llm_provider_id` FK is only populated for actual API calls.

3. **Polymorphic collection items** — v1's `story_steps` had separate FKs for prompt, version, and library entry. Polymorphic `item_type` + `item_id` is cleaner and more flexible.

4. **No `prompt_environments`** — Environment overrides add complexity for minimal benefit in a self-hosted tool. If needed later, a JSON column `environment_pins` on prompts achieves the same without a separate table.

---

## UX Architecture: The Workspace

### Core principle: 5-6 page navigations reduced to 1 screen

v1 flow to go from "I have a prompt idea" to "I've compared results":
1. Navigate to prompts/create
2. Navigate to prompts/{id}/versions/create
3. Navigate to prompts/{id}/runs/create
4. Navigate to prompt-runs/{id}
5. Navigate to library/create
6. Navigate to library/compare

**v2 flow: 1 screen.**

### 4 screens total (down from ~15)

#### Screen 1: Dashboard (`/dashboard`)
Recent prompts, recent results, starred results, quick-create prompt inline (no page navigation).

#### Screen 2: Prompt Workspace (`/prompts/{slug}`) — the main screen

```
+------------------+--------------------------+---------------------+
| VERSION SIDEBAR  |     EDITOR PANEL         |   RESULTS PANEL     |
|                  |                          |                     |
| v3 (active) *    | Text editor with:        | Result cards:       |
| v2               | - {{var}} highlighting   | - GPT-4o * 4/5     |
| v1               | - {{>slug}} highlighting | - Claude 3.5        |
|                  | - Inline autocomplete    | - [Paste result]    |
| [+ New Version]  | - Text / Visual toggle   | - [Import .md]      |
|                  |                          | - [Compare selected]|
| Metadata:        | Variable metadata below  | - [Export all .md]  |
| Name, Tags, Cat  |                          |                     |
|                  | [Save Version] [Run LLM] |                     |
+------------------+--------------------------+---------------------+
```

Everything happens here: create versions, paste results, rate, star, compare, import, export — zero page navigation for core workflows.

**Editor panel features:**
- Syntax highlighting for `{{variables}}` and `{{>includes}}` via transparent overlay
- Autocomplete popup when typing `{{` (known variables) or `{{>` (available fragments)
- Inline variable creation: type `{{new_var}}` and it becomes a known variable
- Toggle between "Text" and "Visual" mode (drag-and-drop composer)
- Variable metadata editor slides open when variables are detected

**Results panel features:**
- Each result card shows provider, model, rating, starred status, truncated preview
- Expandable to full text
- "Compare" button for 2-4 selected results (opens side-by-side modal)
- Manual paste form inline
- Import .md via file picker

#### Screen 3: Browse (`/browse`)
Tabbed view: Prompts | Fragments | Starred Results | Collections.
Filter by category, tags, search text. Click any item to go to its Workspace.

#### Screen 4: Settings (`/settings`)
Tabbed: LLM Providers | API Keys | Users | Categories | Profile. All inline CRUD.

### Key interaction patterns

1. **Inline variable/include creation** — Type `{{` to trigger autocomplete showing all known variables across the system. Select existing or type new name and close with `}}` to create on the fly. Same for `{{>` with fragments.

2. **Drag-and-drop composer** — Available as a mode toggle in the editor. The palette of variables and fragments lives in a collapsible sidebar within the editor panel.

3. **Results comparison** — Select 2-4 results via checkboxes, click "Compare", full-width modal with side-by-side columns. Optional "Summarize differences" button (requires configured LLM).

4. **Copy/export** — Every result card has copy-to-clipboard and export-to-.md buttons. "Export all" generates a single .md file with prompt + all results formatted.

5. **Version diffing** — Click two versions in the sidebar for side-by-side diff (client-side JS diff library).

---

## Livewire Component Architecture

### Component tree

```
app/Livewire/
|-- Dashboard.php
|-- Browse/
|   |-- PromptList.php
|   |-- StarredResults.php
|   +-- CollectionList.php
|-- Workspace/
|   |-- WorkspacePage.php          # orchestrator
|   |-- VersionSidebar.php
|   |-- Editor.php                 # wire:model.live for variable detection
|   |-- VisualComposer.php         # drag-and-drop (Alpine + SortableJS)
|   |-- VariableMetadata.php
|   |-- ResultsPanel.php
|   |-- ResultCard.php
|   |-- CompareModal.php
|   |-- ManualResultForm.php
|   |-- ImportResults.php
|   |-- RunWithLlm.php
|   +-- PromptMetadata.php
|-- Settings/
|   |-- SettingsPage.php
|   |-- LlmProviders.php
|   |-- ApiKeys.php
|   |-- UserManagement.php
|   +-- Categories.php
+-- Shared/
    |-- SearchBar.php
    |-- TagInput.php
    +-- MarkdownPreview.php
```

### Event flow (WorkspacePage orchestrates)

```
VersionSidebar --[version-selected]--> WorkspacePage --> Editor (loads content)
Editor --[content-changed]--> WorkspacePage --> VariableMetadata (updates detected vars)
Editor --[save-version]--> WorkspacePage --> VersionSidebar (refreshes list)
RunWithLlm --[run-completed]--> WorkspacePage --> ResultsPanel (refreshes)
ManualResultForm --[result-saved]--> ResultsPanel (refreshes)
```

### Responsibility split

**Alpine.js** (must be client-side for responsiveness):
- Drag-and-drop in VisualComposer (SortableJS)
- Autocomplete dropdown positioning and keyboard navigation
- Textarea auto-resize, copy-to-clipboard
- Version diff rendering (client-side JS diff library)
- Panel resize handles

**Livewire** (needs server-side logic):
- Saving versions (VersioningService)
- Running LLM calls (LlmDispatchService)
- CRUD for results, collections, categories
- Search and filtering
- Template rendering (TemplateEngine)
- Import/export file handling

### Navigation

All screen transitions use `wire:navigate` for SPA-like page swaps with browser history support.

---

## Import/Export Strategy

### Export format (markdown with YAML frontmatter)

**Prompt export** (`{slug}-v{n}.md`):
```markdown
---
prompt: my-prompt-name
slug: my-prompt-name
version: 3
created: 2026-03-15T10:30:00Z
variables: [tone, audience, topic]
includes: [system-context, output-format]
---

You are a {{tone}} assistant helping {{audience}}.

{{>system-context}}

Please write about {{topic}}.

{{>output-format}}
```

**Result export** (`{slug}-v{n}-{provider}-{id}.md`):
```markdown
---
prompt: my-prompt-name
version: 3
provider: OpenAI
model: gpt-4o
source: api
rating: 4
starred: true
date: 2026-03-15T10:35:00Z
---

## Prompt (as sent)

You are a friendly assistant helping developers...

## Response

Here is the response text from the LLM...

## Notes

This was a good response, particularly the second paragraph.
```

**Collection export** (`collection-{slug}.md`):
```markdown
---
collection: My Prompt Journey
created: 2026-03-15
items: 5
---

# My Prompt Journey

## Step 1: Initial Version
### Prompt (my-prompt v1)
[prompt content]

### Result (Claude 3.5 Sonnet)
[result content]

*Notes: Started with this basic approach...*

---

## Step 2: Refined Version
...
```

### Import strategy

- **Single result**: Upload .md, parse YAML frontmatter for metadata. If no frontmatter, treat entire content as response text and prompt user for metadata.
- **Bulk import**: Upload multiple .md files, each processed independently, attached to currently open prompt version.
- **Implementation**: Livewire `ImportResults` component with `wire:model` file upload. Server-side YAML parsing via `symfony/yaml` (already a Laravel dependency).

---

## LLM Integration (3 tiers)

### Tier 1: Manual workflow (no API keys needed)
Type/paste prompts, paste results from any LLM web interface, import results from .md files, compare, rate, star, organize into collections, export to .md.

### Tier 2: API-assisted workflow (optional, per-provider)
Configure providers in Settings, "Run with LLMs" button sends rendered prompt to selected providers, results saved with `source: 'api'`, token counts and duration tracked.

### Tier 3: AI-powered features (optional, uses single configured provider)
- "Summarize differences" between prompt versions or results
- "Merge responses" — combine best parts of multiple results
- "Suggest improvements" — analyze a prompt and suggest refinements
- Uses a configured "utility LLM" via new `AiAssistantService`

### Driver architecture
All 6 v1 drivers carry forward unchanged. Single addition:
```php
interface LlmDriverInterface
{
    public function complete(string $prompt): LlmResult;
    public function completeWithSystem(string $system, string $prompt): LlmResult;
}
```

---

## Services

### Carried forward from v1

| Service | Notes |
|---|---|
| TemplateEngine | Core rendering logic — carry forward verbatim |
| VersioningService | Transactional version numbering |
| LlmDispatchService | Driver dispatch pattern |
| All 6 LLM drivers | Add `completeWithSystem()` for AI assistant features |
| ApiKeyService | Key generation and hashing |

### New in v2

| Service | Purpose |
|---|---|
| ImportExportService | .md import/export with YAML frontmatter |
| AiAssistantService | AI-powered comparison, merge, suggestions |

---

## v1 Data Migration

Artisan command: `php artisan urge:import-v1 {path-to-v1-database}`

| v1 source | v2 target | Notes |
|---|---|---|
| users | users | Direct copy, passwords and roles preserved |
| categories | categories | Direct copy |
| prompts | prompts | Set `type = 'prompt'`, map `active_version_id` to `pinned_version_id` |
| prompt_versions | prompt_versions | Direct copy (immutable data) |
| llm_providers | llm_providers | Direct copy (requires same APP_KEY for encrypted keys) |
| prompt_runs + llm_responses | results | Each response becomes a result with `source: 'api'` |
| library_entries | results | `source: 'manual'`, `starred: true`, with deduplication |
| stories | collections | Direct copy of title, description |
| story_steps | collection_items | Map to polymorphic items |
| prompt_environments | (dropped) | Log a message noting overrides for manual review |
| api_keys + pivot | api_keys + pivot | Direct copy |

The import is idempotent, transactional, and never modifies the v1 database.

---

## Directory Structure

```
urge-v2/
|-- app/
|   |-- Http/
|   |   |-- Controllers/
|   |   |   |-- Api/                    # API controllers (carried forward)
|   |   |   +-- Auth/                   # Breeze auth controllers
|   |   +-- Middleware/
|   |       |-- ApiKeyAuthentication.php
|   |       +-- RequireRole.php
|   |-- Livewire/                       # All Livewire components (see tree above)
|   |-- Models/
|   |   |-- User.php
|   |   |-- Prompt.php
|   |   |-- PromptVersion.php
|   |   |-- Result.php
|   |   |-- Collection.php
|   |   |-- CollectionItem.php
|   |   |-- Category.php
|   |   |-- LlmProvider.php
|   |   +-- ApiKey.php
|   |-- Policies/
|   +-- Services/
|       |-- TemplateEngine.php
|       |-- VersioningService.php
|       |-- LlmDispatchService.php
|       |-- AiAssistantService.php
|       |-- ImportExportService.php
|       |-- ApiKeyService.php
|       +-- LlmProviders/
|-- config/
|   +-- urge.php
|-- database/
|   +-- migrations/                     # 10 migrations (down from 22)
|-- resources/
|   |-- css/
|   |-- js/
|   |   |-- app.js
|   |   |-- composer.js                 # SortableJS drag-and-drop
|   |   |-- autocomplete.js             # {{variable}} autocomplete
|   |   +-- diff.js                     # Client-side version diffing
|   +-- views/
|       |-- components/
|       |-- layouts/
|       |-- livewire/                   # Livewire component views
|       +-- auth/
|-- routes/
|   |-- web.php                         # ~15 lines (4 page routes + auth)
|   |-- api.php
|   +-- auth.php
|-- tests/
+-- public/
```

---

## MVP Phases

### Phase 1: Core Workspace
Fresh Laravel 12 + Livewire 3 setup, migrations, models, TemplateEngine + VersioningService, auth/roles, WorkspacePage with Editor + VersionSidebar + ManualResultForm + ResultsPanel, Dashboard, basic layout with wire:navigate.

**Delivers:** Create prompt -> version -> paste result -> star/rate, all on one screen.

### Phase 2: Rich Editing + Comparison
Inline autocomplete for `{{var}}` and `{{>slug}}`, VisualComposer (drag-and-drop), CompareModal (side-by-side results), version diffing, VariableMetadata, fragment support.

### Phase 3: Import/Export + Browse + Collections
ImportExportService, .md import/export, copy-to-clipboard, Browse page with filters, Collections CRUD.

### Phase 4: LLM Integration + AI Features
Carry forward 6 drivers, RunWithLlm component, LlmProviders settings, AiAssistantService (summarize, merge).

### Phase 5: API + Migration + Polish
API controllers + middleware, `urge:import-v1` command, API key management, user management, tests, Hostinger deployment docs.

---

## Verification checklist

1. **Workspace flow**: Create prompt -> save version -> paste result -> star -> compare two results -> export .md
2. **Fragment flow**: Create fragment -> use `{{>slug}}` in a prompt -> verify rendering
3. **Import flow**: Export a result as .md -> re-import -> verify metadata preserved
4. **Collection flow**: Create collection -> add versions and results -> reorder -> export
5. **Run tests**: `composer test` after each phase
6. **Deploy test**: Build assets locally (`npm run build`), deploy to Hostinger, verify Livewire components work
