# URGE v2 — Continuation Prompt

Copy and paste the relevant phase section below as your prompt to continue development.

---

## Phase 2: Rich Editing + Comparison

```
You are continuing development of URGE v2, a Laravel 12 + Livewire 3 prompt management system. Phase 1 is complete. Read CLAUDE.md for full context.

Implement Phase 2 — Rich Editing + Comparison. Here's exactly what to build:

### 1. Inline Autocomplete (Alpine.js component)

Create `resources/js/autocomplete.js` — an Alpine.js component that:
- Attaches to the Editor textarea
- Detects when the user types `{{` and shows a dropdown of known variable names
- Detects when the user types `{{>` and shows a dropdown of available fragment slugs
- Populates variable names from: GET /api/internal/variables (new endpoint returning all unique variable names across all prompt_versions)
- Populates fragment slugs from: GET /api/internal/fragments (new endpoint returning all prompts where type='fragment')
- Keyboard navigation: arrow keys to select, Enter/Tab to insert, Escape to dismiss
- Positions dropdown below cursor using textarea caret coordinates

Wire this into `Editor.blade.php` by wrapping the textarea in an Alpine `x-data="autocomplete()"` component.

### 2. Variable Metadata Editor

Create `app/Livewire/Workspace/VariableMetadata.php` and its view:
- Receives detected variables from Editor via event
- For each variable, shows inline fields: type (string|text|enum|number|boolean), default value, description
- For enum type: comma-separated options field
- Saves to the version when Editor saves (pass metadata through the save flow)
- Listen to `version-selected` to load existing metadata from the selected version

Add this component to workspace-page.blade.php below the Editor panel.

### 3. Visual Composer (Drag-and-Drop)

Create `app/Livewire/Workspace/VisualComposer.php` and view:
- Toggle between "Text" and "Visual" mode in the Editor toolbar
- Visual mode shows the prompt as draggable blocks: text blocks, variable chips, include chips
- Uses SortableJS (install via npm: `npm install sortablejs`)
- Create `resources/js/composer.js` for the Alpine+SortableJS integration
- Blocks can be reordered via drag-and-drop
- Adding a variable: button opens picker, inserts `{{var}}` block
- Adding an include: button opens picker, inserts `{{>slug}}` block
- Text blocks are editable inline
- When switching back to "Text" mode, serialize blocks back to template string
- Sync content with Editor component via Livewire events

### 4. Version Diff

Create `resources/js/diff.js`:
- Install jsdiff: `npm install diff`
- Alpine component that takes two version contents and renders a side-by-side diff
- Green for additions, red for deletions, gray for unchanged

Add diff UI to VersionSidebar:
- Shift+click a second version to compare
- Opens a modal/panel showing the diff between the two selected versions

### 5. Compare Modal

Create `app/Livewire/Workspace/CompareModal.php` and view:
- Triggered from ResultsPanel when 2-4 results are selected (add checkboxes to ResultCard)
- Side-by-side columns showing each result's response_text
- Header per column: provider_name, model_name, rating
- Full-width modal overlay

Add selection state and "Compare Selected" button to ResultsPanel.

### 6. Fragment Support in Browse

Update `app/Livewire/Browse.php`:
- Already has tab filtering for prompts/fragments — verify it works
- Add result counts per prompt (withCount('results'))

### Testing

Add tests in `tests/Feature/` for:
- VariableMetadata saving and loading
- Autocomplete endpoints returning correct data
- CompareModal rendering with multiple results

Run `php artisan test` and ensure all tests pass.
```

---

## Phase 3: Import/Export + Browse + Collections

```
You are continuing development of URGE v2, a Laravel 12 + Livewire 3 prompt management system. Phases 1-2 are complete. Read CLAUDE.md for full context.

Implement Phase 3 — Import/Export, Collections, and enhanced Browse.

### 1. ImportExportService

Create `app/Services/ImportExportService.php`:

**Export methods:**
- `exportPromptVersion(PromptVersion): string` — generates markdown with YAML frontmatter:
  ```
  ---
  prompt: {slug}
  version: {number}
  created: {iso8601}
  variables: [list]
  includes: [list]
  ---
  {content}
  ```
- `exportResult(Result): string` — markdown with frontmatter:
  ```
  ---
  prompt: {slug}
  version: {number}
  provider: {provider_name}
  model: {model_name}
  source: {source}
  rating: {rating}
  starred: {starred}
  date: {iso8601}
  ---
  ## Response
  {response_text}
  ## Notes
  {notes}
  ```
- `exportCollection(Collection): string` — full narrative markdown

**Import methods:**
- `parseMarkdownWithFrontmatter(string $content): array` — returns ['meta' => [...], 'body' => '...']
  Use Symfony YAML component (already available via Laravel) to parse frontmatter between `---` delimiters
- `importResult(string $content, PromptVersion $version, User $user): Result` — parses frontmatter for metadata, creates Result with source='import'

### 2. Import Component

Create `app/Livewire/Workspace/ImportResults.php` and view:
- File upload using `WithFileUploads` trait
- Accept .md files (single or multiple via array)
- Parse each file through ImportExportService
- Attach results to current prompt version
- Show import summary (count, any errors)

Add to workspace results panel area.

### 3. Export Buttons

Add to ResultsPanel view:
- Per-result "Export .md" button that triggers a download
- "Export All" button that generates a zip of all results for current version

Add to Editor toolbar:
- "Export Prompt" button that downloads the current version as .md

Implement downloads via Livewire's `$this->streamDownload()`.

### 4. Copy to Clipboard

Ensure every text area has a copy button (already partially done in ResultsPanel).
Add to Editor toolbar: "Copy Rendered" button that:
- Calls TemplateEngine::render() with empty variables
- Copies the resolved content (includes expanded) to clipboard
- Uses Alpine.js `navigator.clipboard.writeText()`

### 5. Collections (new model + migration)

Create migration: `php artisan make:migration create_collections_table`
- collections: id, title, description, created_by, timestamps
- collection_items: id, collection_id (FK cascade), sort_order, item_type, item_id, notes, timestamps

Create models: `Collection`, `CollectionItem` (with relationships).

Create `app/Livewire/Browse/CollectionList.php` and view:
- CRUD for collections
- Add items to collection from workspace (button on version sidebar + results panel: "Add to Collection")
- Reorder items via SortableJS
- View collection as a narrative page

Add "Collections" tab to Browse.

### 6. Enhanced Browse

Update Browse component:
- Add "Starred Results" tab showing Result::where('starred', true)
- Add category filter dropdown
- Add tag filter (clickable tag chips)
- Show result count per prompt

### Testing

- Test ImportExportService round-trip: export a result, import it, verify data matches
- Test Collection CRUD and ordering
- Test file upload import flow
- Run `php artisan test` — all tests pass
```

---

## Phase 4: LLM Integration + AI Features

```
You are continuing development of URGE v2, a Laravel 12 + Livewire 3 prompt management system. Phases 1-3 are complete. Read CLAUDE.md for full context.

Implement Phase 4 — LLM API integration and AI-powered features.

### 1. Port LLM Drivers from v1

The v1 codebase is at /home/user/urge (or wherever the v1 repo lives). Copy these files, adapting namespaces:

- `app/Services/LlmProviders/Contracts/LlmDriverInterface.php` — add `completeWithSystem(string $system, string $prompt): LlmResult`
- `app/Services/LlmProviders/LlmResult.php`
- `app/Services/LlmProviders/OpenAiDriver.php`
- `app/Services/LlmProviders/AnthropicDriver.php`
- `app/Services/LlmProviders/MistralDriver.php`
- `app/Services/LlmProviders/GeminiDriver.php`
- `app/Services/LlmProviders/OllamaDriver.php`
- `app/Services/LlmProviders/OpenRouterDriver.php`
- `app/Services/LlmDispatchService.php`

Add default `completeWithSystem` implementation to each driver (prepend system message to prompt for drivers that don't natively support it; use system parameter for Anthropic/OpenAI).

### 2. LLM Provider Settings

Create `app/Livewire/Settings/LlmProviders.php` and view:
- List all LlmProvider records
- Inline create/edit form: name, driver (dropdown), api_key (password field), model, endpoint (optional)
- Toggle is_active
- Test connection button (calls driver->complete("Hello") and shows success/error)
- Delete with confirmation

Update Settings.php to include this as a tab.

### 3. RunWithLlm Component

Create `app/Livewire/Workspace/RunWithLlm.php` and view:
- Shows active LLM providers as checkboxes
- Variable fill form: for each detected variable in current version, show an input field
- "Run" button:
  1. Renders the prompt via TemplateEngine with provided variables
  2. Dispatches to each selected provider via LlmDispatchService
  3. Creates a Result per provider with source='api', including rendered_content, variables_used, token counts, duration
  4. Dispatches 'result-saved' event
- Show loading state per provider
- Show errors inline if a provider fails

Add to workspace layout, triggered via a "Run with LLMs" button in the Editor toolbar.

### 4. AiAssistantService

Create `app/Services/AiAssistantService.php`:
- Constructor takes LlmDispatchService
- `summarizeDifferences(string $textA, string $textB, LlmProvider $provider): string` — sends a meta-prompt asking the LLM to summarize differences
- `suggestImprovements(string $promptContent, LlmProvider $provider): string` — sends a meta-prompt asking for prompt improvements
- Uses `completeWithSystem()` with a system message establishing the assistant role

Add "Summarize Differences" button to CompareModal (from Phase 2).
Add "Suggest Improvements" button to Editor toolbar (optional, only shown if providers configured).

### Testing

- Test LlmDispatchService with a mock driver
- Test RunWithLlm creates results correctly
- Test AiAssistantService prompt construction
- Run `php artisan test` — all tests pass
```

---

## Phase 5: API Layer + v1 Migration + Polish

```
You are continuing development of URGE v2, a Laravel 12 + Livewire 3 prompt management system. Phases 1-4 are complete. Read CLAUDE.md for full context.

Implement Phase 5 — REST API, v1 data migration, and production polish.

### 1. API Layer

Port from v1, adapting to v2 models:

Create `app/Http/Middleware/ApiKeyAuthentication.php`:
- Bearer token auth: hash token with SHA-256, look up in api_keys table
- Set authenticated user, attach api_key to request
- Rate limiting per key (config: urge.api_rate_limit, urge.api_rate_window)

Create `app/Services/ApiKeyService.php`:
- generateKey(): creates random key with prefix, stores SHA-256 hash
- Key preview (first 8 chars stored for display)

Create migration for api_keys table:
- id, name, user_id (FK), key_hash, key_preview, last_used_at, expires_at, is_active, timestamps
- api_key_prompt pivot table (scope keys to specific prompts)

Create API controllers in `app/Http/Controllers/Api/`:
- `ApiController.php` — base with JSON response helpers
- `PromptController.php` — CRUD prompts, list versions
- `VersionController.php` — get version, render with variables
- `HealthController.php` — simple health check

Register in `routes/api.php` with prefix `/api/v1/`, middleware `api.auth`.

### 2. API Key Settings

Create `app/Livewire/Settings/ApiKeys.php` and view:
- Create key: name, optional prompt scoping
- Show generated key ONCE (modal), then only preview
- List keys: name, preview, last_used, active toggle, delete
- Add as tab in Settings

### 3. User Management Settings

Create `app/Livewire/Settings/UserManagement.php` and view:
- List users with role badges
- Change user roles (admin only)
- Delete users (not self)
- Add as tab in Settings (admin only)

### 4. Category Management Settings

Create `app/Livewire/Settings/Categories.php` and view:
- CRUD categories: name, color picker
- Show prompt count per category
- Add as tab in Settings

### 5. v1 Data Migration Command

Create `app/Console/Commands/ImportV1Command.php`:
- Artisan command: `php artisan urge:import-v1 {path-to-v1-database}`
- Opens v1 SQLite read-only
- Mapping (all in a single transaction):
  - users → users (direct copy, preserve passwords/roles)
  - categories → categories
  - prompts → prompts (type='prompt', map active_version_id to pinned_version_id)
  - prompt_versions → prompt_versions (direct copy)
  - llm_providers → llm_providers (requires same APP_KEY)
  - prompt_runs + llm_responses → results (source='api', copy rendered_content + variables_used)
  - library_entries → results (source='manual', starred=true, deduplicate against API results)
  - stories → collections
  - story_steps → collection_items (polymorphic mapping)
  - api_keys + pivot → api_keys + pivot
- Uses firstOrCreate for idempotency
- Logs every action to console
- The v1 database is NEVER modified

### 6. Polish

- Add `composer dev` script to composer.json (artisan serve + npm run dev in parallel)
- Update urge.php config with all settings (api rate limit, key prefix, key bytes, etc.)
- Add form validation to all Livewire components (proper $rules arrays)
- Add loading states (wire:loading) to all buttons that trigger server actions
- Add flash messages for success/error states
- Responsive layout: workspace panels stack on mobile
- Add keyboard shortcuts via Alpine.js: Ctrl+S to save version, Ctrl+Enter to run
- Cache optimization: eager load relationships, add indexes review

### Testing

- Test API authentication and authorization
- Test API endpoints CRUD
- Test ImportV1Command with a fixture v1 database
- Test ApiKeyService key generation and hashing
- Test role-based access (admin/editor/viewer permissions)
- Full integration test: create prompt via API, run via web, export, import
- Run `php artisan test` — all tests pass
```

---

## Usage

1. Copy the phase section you need
2. Start a new Claude Code session in the urge-v2 directory
3. Paste the prompt
4. Claude will read CLAUDE.md for context and implement the phase
