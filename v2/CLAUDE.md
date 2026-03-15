# CLAUDE.md

## Project Overview

URGE v2 is a self-hosted prompt management system for LLM developers. Ground-up rebuild from v1 using Livewire 3 for a reactive single-screen workspace experience.

**Stack:** Laravel 12 / PHP 8.3+, Livewire 3, Alpine.js, Tailwind CSS, SQLite, Vite

## Build & Dev Commands

```bash
composer install         # Install PHP dependencies
npm install              # Install JS dependencies
cp .env.example .env     # Create env file
php artisan key:generate # Generate app key
touch database/database.sqlite
php artisan migrate      # Run database migrations
php artisan test         # Run PHPUnit tests (48 tests)
composer dev             # Full dev stack (if script exists)
php artisan serve        # Dev server at http://127.0.0.1:8000
npm run dev              # Vite dev server with HMR
npm run build            # Production build
```

## Architecture

### Data Flow

```
Prompt (type: prompt|fragment) → PromptVersion[] (immutable) → Result[] (source: api|manual|import)
Collection → CollectionItem[] (polymorphic: prompt_version|result)
```

### Core Models (7 domain tables)

- **Prompt** — name, slug (auto-generated, unique), type (prompt|fragment), category_id, tags (JSON), pinned_version_id (nullable; NULL = latest is active). Soft deletes.
- **PromptVersion** — immutable (LogicException on update). Auto-numbered per prompt. Extracts variables/includes on create. Has commit_message, variable_metadata (JSON).
- **Result** — unified entity replacing v1's PromptRun+LlmResponse+LibraryEntry. Fields: source (api|manual|import), provider_name (free text), model_name (free text), llm_provider_id (FK, nullable), response_text, rating (1-5), starred (boolean replaces Library concept), notes, token counts, duration_ms.
- **Category** — name, slug (auto-generated), color
- **LlmProvider** — name, driver, api_key (encrypted), model, endpoint, settings (JSON)
- **Collection** — title, description (Phase 3)
- **CollectionItem** — polymorphic item_type+item_id, sort_order, notes (Phase 3)

### Services

- **TemplateEngine** (`app/Services/TemplateEngine.php`) — `{{variable}}` substitution, `{{>slug}}` recursive include resolution, circular reference detection, max depth config
- **VersioningService** (`app/Services/VersioningService.php`) — transactional version creation, auto-numbering, variable/include extraction, metadata filtering

### Livewire Components

```
app/Livewire/
├── Dashboard.php              # Recent prompts, starred results, inline create
├── Browse.php                 # Tabbed prompts/fragments, search
├── Settings.php               # Placeholder (Phase 4-5)
└── Workspace/
    ├── WorkspacePage.php      # 3-panel orchestrator
    ├── Editor.php             # Textarea, live variable detection, save version
    ├── VersionSidebar.php     # Version list, select, pin indicator
    ├── ResultsPanel.php       # Results list, star, rate, expand, delete
    ├── ManualResultForm.php   # Paste result with provider/model/notes/rating
    └── PromptMetadata.php     # Name, type, category, tags, description
```

### Livewire Event Flow

```
VersionSidebar --[version-selected]--> WorkspacePage --> Editor, ResultsPanel, ManualResultForm
Editor --[version-created]--> WorkspacePage --> VersionSidebar, ResultsPanel
ManualResultForm --[result-saved]--> ResultsPanel
```

### Routes (4 screens)

```
/ → redirect to /dashboard
/dashboard          → Dashboard (Livewire)
/browse             → Browse (Livewire)
/prompts/{slug}     → WorkspacePage (Livewire) — the main screen
/settings           → Settings (Livewire)
```

All navigation uses `wire:navigate` for SPA-like transitions.

### Auth & Roles

Breeze (Blade stack). Roles: admin, editor, viewer. First registered user auto-becomes admin. `RequireRole` middleware aliased as `role`.

### Template Syntax

- `{{variable_name}}` — variable placeholder (letter/underscore start, alphanumeric)
- `{{>slug}}` — include another prompt's active version content
- Max depth: `URGE_MAX_INCLUDE_DEPTH` env (default 10)

### Key Patterns

- **Blade/Alpine `{{` conflict:** Use `'{' + '{'` string splitting in JS contexts within Blade templates
- **Auto-slug:** Prompt and Category models generate from name in `booted()` with collision counter
- **Immutable versions:** `PromptVersion::booted()` throws `LogicException` on update
- **Active version resolution:** `Prompt::$active_version` accessor returns pinned version if set, otherwise latest

### Config

`config/urge.php` — `max_include_depth`, `curl_ssl_verify`

## Current Status

**Phase 1 complete.** Core workspace functional: create prompt, save versions, paste results, star/rate.

Remaining phases: 2 (rich editing + comparison), 3 (import/export + browse + collections), 4 (LLM integration), 5 (API + v1 migration + polish).
