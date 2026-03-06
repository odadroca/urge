# URGE — Architecture

## Overview

URGE is a self-hosted prompt management system built on **Laravel 12**. It provides a web UI for managing LLM prompts with full version history, and a REST API for programmatic access from external applications.

---

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Auth scaffolding | Laravel Breeze (Blade + Alpine.js + Tailwind CSS) |
| Database | SQLite (`storage/app/database.sqlite`) |
| Frontend build | Vite |
| PHP | 8.3+ |

No queue workers, no Redis, no external services required. Everything runs from a single PHP process, making it suitable for cheap shared hosting.

---

## Directory Structure

```
app/
  Http/
    Controllers/
      Api/
        ApiController.php          # Base controller: JSON response helpers
        PromptController.php       # GET prompts, GET prompt, POST render
        VersionController.php      # GET versions list, GET specific version
      Web/
        DashboardController.php    # Overview stats
        PromptController.php       # Full CRUD for prompts
        PromptVersionController.php  # Version history, create, activate, compare
        PromptRunController.php    # Execute prompts against LLM providers
        LlmResponseController.php  # Rate and export LLM responses
        LibraryController.php      # Response library CRUD and compare
        StoryController.php        # Story CRUD
        StoryStepController.php    # Story step ordering and management
        ApiKeyController.php       # API key management
        UserController.php         # Admin: create, edit, delete users
        Admin/
          LlmProviderController.php  # Admin: configure LLM providers
      Auth/                        # Breeze-generated auth controllers
    Middleware/
      ApiKeyAuthentication.php     # Validates Bearer token on all API routes
      RequireRole.php              # Role gate for admin-only web routes
  Models/
    User.php                       # role column + isAdmin()/isEditor() helpers
    Prompt.php                     # slug auto-generation on create
    PromptVersion.php              # Immutable: updating() throws LogicException
    ApiKey.php                     # key_hash, key_encrypted, key_preview
    LlmProvider.php                # LLM provider config (driver, model, encrypted API key)
    PromptRun.php                  # Prompt execution record
    LlmResponse.php                # Individual LLM response with rating
    LibraryEntry.php               # Curated saved response
    Story.php                      # Multi-step prompt sequence
    StoryStep.php                  # Ordered step within a story
  Policies/
    PromptPolicy.php               # view/create/update/delete/createVersion/activateVersion
    ApiKeyPolicy.php               # delete (owner or admin)
    UserPolicy.php                 # admin-only
  Services/
    TemplateEngine.php             # {{var}} extraction and substitution
    ApiKeyService.php              # Key generation, storage, lookup
    VersioningService.php          # Transactional version number assignment
    LlmDispatchService.php         # Routes prompt runs to the correct LLM driver
    LlmProviders/
      Contracts/
        LlmDriverInterface.php     # complete(string $prompt): array
      OpenAiDriver.php
      AnthropicDriver.php
      MistralDriver.php
      GeminiDriver.php
      OllamaDriver.php
      OpenRouterDriver.php

bootstrap/
  app.php                          # Middleware aliases: 'role', 'api.key'

routes/
  web.php                          # Session-authenticated web routes
  api.php                          # /api/v1/* with ApiKeyAuthentication

config/
  urge.php                         # key_prefix, key_bytes, variable_pattern

documentation/                     # This folder
```

---

## Data Model

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `email` | string UNIQUE | Login credential |
| `password` | string | Bcrypt hash |
| `role` | string | `admin`, `editor`, or `viewer` |
| `remember_token` | string | |
| `created_at / updated_at` | timestamp | |

**First registered user** automatically receives the `admin` role. Subsequent registrations default to `viewer`.

---

### `prompts`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | Display name |
| `slug` | string UNIQUE | Auto-generated from name on creation; never changes after that |
| `description` | text NULLABLE | |
| `tags` | json NULLABLE | Array of tag strings for organisation and filtering |
| `active_version_id` | bigint NULLABLE FK → prompt_versions.id | Points to the published version |
| `created_by` | bigint FK → users.id | |
| `deleted_at` | timestamp NULLABLE | Set on archive (soft-delete); NULL means the prompt is active |
| `created_at / updated_at` | timestamp | |

Prompts use **soft deletes** (`SoftDeletes` trait). Archiving a prompt sets `deleted_at`; restoring clears it. Soft-deleted prompts are excluded from all API responses and the default web list. The slug is reserved even after soft-deletion (slug uniqueness check uses `withTrashed()`) so that slugs are never recycled.

The slug is generated once at creation time (`Str::slug($name)` with a numeric suffix if taken) and is **locked permanently**. This guarantees API consumers always use the same URL regardless of prompt renames.

---

### `prompt_versions`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_id` | bigint FK → prompts.id CASCADE | |
| `version_number` | unsigned int | Scoped per prompt (1, 2, 3…). Unique per prompt. |
| `content` | longtext | Prompt text with `{{variable}}` and `{{>slug}}` placeholders |
| `commit_message` | string(500) NULLABLE | |
| `variables` | json | Extracted `{{variable}}` names, auto-populated on save |
| `variable_metadata` | json NULLABLE | Per-variable metadata: `type`, `description`, `default`, and (for enum) `options` |
| `includes` | json NULLABLE | Extracted `{{>slug}}` include references, auto-populated on save |
| `created_by` | bigint FK → users.id | |
| `created_at` | timestamp | **No `updated_at`** — versions are immutable |

**Immutability** is enforced at the model level: `PromptVersion::updating()` throws a `LogicException`. Once written, a version record cannot be changed.

**Version numbering** uses a database transaction with `MAX(version_number) + 1` scoped to `prompt_id`, ensuring no gaps or races (SQLite serialises writes at the table level).

**`variable_metadata` structure** — each key is a variable name, and the value is an object:

```json
{
  "customer_name": {
    "type": "string",
    "description": "Customer's full name",
    "default": null
  },
  "status": {
    "type": "enum",
    "description": "Order status",
    "default": "pending",
    "options": ["pending", "shipped", "delivered"]
  }
}
```

Valid `type` values: `string`, `text`, `number`, `boolean`, `enum`. For `enum`, `options` holds the list of allowed values shown in the UI.

---

### `api_keys`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | bigint FK → users.id CASCADE | Owner |
| `name` | string | Human label (e.g. "Production App") |
| `key_hash` | string(64) UNIQUE | `hash('sha256', $rawKey)` — used for authentication lookup |
| `key_encrypted` | text | `Crypt::encryptString($rawKey)` — allows admin retrieval if needed |
| `key_preview` | string(12) | First 8 characters + `...` — shown in the UI list |
| `last_used_at` | timestamp NULLABLE | Updated on each authenticated request |
| `expires_at` | timestamp NULLABLE | NULL means never expires |
| `created_at / updated_at` | timestamp | |

---

### `api_key_prompt` (pivot)
| Column | Type | Notes |
|---|---|---|
| `api_key_id` | bigint FK → api_keys.id CASCADE | |
| `prompt_id` | bigint FK → prompts.id CASCADE | |

Joins an API key to the specific prompts it is allowed to access. When an API key has no rows in this table, it is **unscoped** and can access all prompts. When it has one or more rows, it is **scoped** and only those prompts are accessible (others return `403 KEY_SCOPE_DENIED`).

---

### `prompt_environments`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_id` | bigint FK → prompts.id CASCADE | |
| `name` | string(50) | Environment name (e.g. `production`, `staging`, any custom string) |
| `prompt_version_id` | bigint FK → prompt_versions.id CASCADE | The version assigned to this environment |
| `created_at / updated_at` | timestamp | |

Unique constraint on `(prompt_id, name)` — each prompt can only have one assignment per environment name. Used to resolve the `"environment"` parameter in the render API endpoint.

---

### `llm_providers`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `driver` | string | `openai`, `anthropic`, `mistral`, `gemini`, `ollama`, `openrouter` |
| `name` | string | Display name shown in the UI |
| `model` | string | Model identifier sent to the provider API (e.g. `gpt-4o-mini`) |
| `base_url` | string NULLABLE | Custom endpoint; used by Ollama (defaults to `http://localhost:11434`) |
| `api_key_encrypted` | text NULLABLE | `Crypt::encryptString()` — AES-256-CBC; NULL for Ollama |
| `enabled` | boolean | Controls whether the provider appears in the run UI |
| `sort_order` | unsigned int | Display order in the provider list |
| `created_at / updated_at` | timestamp | |

Six providers are pre-seeded by migrations (all disabled by default): OpenAI GPT-4o Mini, Anthropic Claude Haiku, Mistral Small, Google Gemini Flash, Ollama, and OpenRouter.

---

### `prompt_runs`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_id` | bigint FK → prompts.id CASCADE | |
| `prompt_version_id` | bigint FK → prompt_versions.id | |
| `rendered_content` | longtext | Prompt text after variable substitution |
| `variables_used` | json NULLABLE | Key-value pairs of variables provided at run time |
| `created_by` | bigint FK → users.id | |
| `created_at` | timestamp | **No `updated_at`** — runs are immutable |

A prompt run is an execution record. It captures the rendered prompt and variables used, then links to one or more `llm_responses`.

---

### `llm_responses`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_run_id` | bigint FK → prompt_runs.id CASCADE | Deleted with the run |
| `llm_provider_id` | bigint FK → llm_providers.id | |
| `model_used` | string | Model identifier at time of execution |
| `response_text` | longtext NULLABLE | The LLM output (NULL on error) |
| `input_tokens` | unsigned int NULLABLE | Token count from provider (if available) |
| `output_tokens` | unsigned int NULLABLE | Token count from provider (if available) |
| `duration_ms` | unsigned int NULLABLE | Wall-clock time for the API call |
| `status` | string | `success` or `error` |
| `error_message` | text NULLABLE | Error details when status is `error` |
| `rating` | unsigned tinyint NULLABLE | 1–5 star rating |
| `rated_by` | bigint FK NULLABLE → users.id | NULL on delete of rater |
| `rated_at` | timestamp NULLABLE | |
| `created_at` | timestamp | **No `updated_at`** |

---

### `library_entries`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_id` | bigint FK → prompts.id CASCADE | |
| `prompt_version_id` | bigint FK → prompt_versions.id CASCADE | |
| `llm_provider_id` | bigint FK NULLABLE → llm_providers.id | NULL on delete of provider |
| `model_used` | string | |
| `response_text` | longtext | The saved LLM response |
| `notes` | text NULLABLE | User-added notes (max 2000 chars in the UI) |
| `rating` | tinyint NULLABLE | 1–5 |
| `created_by` | bigint FK → users.id | |
| `created_at / updated_at` | timestamp | |

Library entries can be created manually or saved directly from a prompt run result.

---

### `stories`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `title` | string | |
| `description` | text NULLABLE | |
| `created_by` | bigint FK → users.id | |
| `created_at / updated_at` | timestamp | |

---

### `story_steps`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `story_id` | bigint FK → stories.id CASCADE | Deleted with the story |
| `sort_order` | unsigned int | Determines step ordering; swapped on move up/down |
| `prompt_id` | bigint FK → prompts.id | |
| `prompt_version_id` | bigint FK → prompt_versions.id | |
| `library_entry_id` | bigint FK NULLABLE → library_entries.id | NULL on delete of entry |
| `notes` | text NULLABLE | |
| `created_at / updated_at` | timestamp | |

---

## Migration Order

The `prompts.active_version_id` → `prompt_versions.id` FK creates a circular dependency. It is resolved by splitting the constraint into a separate final migration:

1. `0001_01_01_000000` — users (Breeze default)
2. `0001_01_01_000001` — cache (Breeze default)
3. `0001_01_01_000002` — jobs (Breeze default)
4. `2024_01_01_000001` — add `role` column to users
5. `2024_01_01_000002` — create prompts *(active_version_id column only, no FK yet)*
6. `2024_01_01_000003` — create prompt_versions *(FK → prompts.id)*
7. `2024_01_01_000004` — create api_keys *(FK → users.id)*
8. `2024_01_01_000005` — add FK from `prompts.active_version_id` → `prompt_versions.id`
9. `2024_01_02_000001` — create llm_providers (with seeded rows)
10. `2024_01_02_000002` — create prompt_runs
11. `2024_01_02_000003` — create llm_responses
12. `2024_01_02_000004` — add OpenRouter provider
13. `2024_01_03_000001` — add `tags` column to prompts
14. `2024_01_03_000002` — create library_entries
15. `2024_01_04_000001` — create stories
16. `2024_01_04_000002` — create story_steps
17. `2024_01_05_000001` — add `deleted_at` (soft deletes) to prompts
18. `2024_01_05_000002` — create api_key_prompt pivot table (scoped keys)
19. `2024_01_05_000003` — add `variable_metadata` column to prompt_versions
20. `2024_01_05_000004` — create prompt_environments table
21. `2024_01_05_000005` — add `includes` column to prompt_versions

---

## Authentication

### Web
Standard Laravel session-based auth via Breeze (`/login`, `/register`, `/logout`).

### API
Custom Bearer token middleware (`ApiKeyAuthentication`). Flow on each request:

1. Extract `Authorization: Bearer <token>` header
2. Compute `hash('sha256', $token)`
3. Look up `api_keys.key_hash`
4. Check `expires_at`
5. Load owner `User` → `Auth::setUser($user)`
6. Update `last_used_at` via raw `DB::table()` (avoids Eloquent overhead on every request)

The raw key is **never stored**. Only its SHA-256 hash (for auth) and AES-256-CBC encrypted form (for optional retrieval) are persisted.

---

## Role & Permission Matrix

| Action | admin | editor | viewer |
|---|---|---|---|
| View prompts / versions | ✓ | ✓ | ✓ |
| Create prompts | ✓ | ✓ | ✗ |
| Edit prompt metadata | ✓ | ✓ | ✗ |
| Create new version | ✓ | ✓ | ✗ |
| Set active version | ✓ | ✓ | ✗ |
| Delete prompt | ✓ | ✗ | ✗ |
| Run prompts | ✓ | ✓ | ✓ |
| Rate LLM responses | ✓ | ✓ | ✓ |
| Manage library entries | ✓ | ✓ | ✓ |
| Create/edit stories | ✓ | ✓ | ✓ |
| Configure LLM providers | ✓ | ✗ | ✗ |
| Manage own API keys | ✓ | ✓ | ✓ |
| Create users | ✓ | ✗ | ✗ |
| Edit user roles | ✓ | ✗ | ✗ |
| Delete users | ✓ | ✗ | ✗ |
| API access (all read + render) | ✓ | ✓ | ✓ |

Permissions are enforced via Laravel Policies registered in `AppServiceProvider`. The `RequireRole` middleware guards admin-only web routes.

---

## Template Engine

`App\Services\TemplateEngine` handles both `{{variable}}` substitution and `{{>slug}}` prompt inclusion.

### Variable syntax
- **Pattern:** `/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/`
- Variable names must start with a letter or underscore (rejects `{{123}}`, `{{has space}}`)
- `extractVariables(string $content): array` — returns unique variable names found in the content (does not follow includes)

### Include syntax
- **Pattern:** `/\{\{>([a-zA-Z0-9_-]+)\}\}/`
- Include slugs may contain letters, digits, underscores, and hyphens
- `extractIncludes(string $content): array` — returns unique include slugs found in the content

### Render pipeline
`render(string $content, array $variables, ?array $metadata, ?string $environment): array`

1. **Resolve includes** — recursively expand all `{{>slug}}` tags by substituting the referenced prompt's content (using the environment version if specified, otherwise the active version)
2. **Merge metadata** — variable metadata from included prompts is merged with the parent's metadata (parent takes precedence on conflicts)
3. **Substitute variables** — replace `{{variable}}` placeholders; apply defaults from metadata for missing variables; leave unmatched placeholders in place
4. **Return** `['rendered', 'variables_used', 'variables_missing', 'includes_resolved']`

**Circular reference detection:** The include resolver tracks the current inclusion chain and throws a `RuntimeException` if a slug appears twice in the chain. The API catches this and returns `422 INCLUDE_ERROR`.

**Max include depth:** Controlled by `config('urge.max_include_depth')` (default 10, configurable via `URGE_MAX_INCLUDE_DEPTH`).

---

## API Key Generation

```
Format:  urge_<62 hex characters>
Length:  67 characters total
Source:  'urge_' . bin2hex(random_bytes(31))
```

`random_bytes()` pulls from the OS CSPRNG, ensuring cryptographic quality randomness.

---

## LLM Dispatch

`App\Services\LlmDispatchService` resolves the correct driver for a given `LlmProvider` record and calls its `complete()` method.

Each driver implements `LlmDriverInterface`:

```php
interface LlmDriverInterface
{
    public function complete(string $prompt): array;
    // Returns: ['response_text', 'input_tokens', 'output_tokens', 'duration_ms']
}
```

Supported drivers: `OpenAiDriver`, `AnthropicDriver`, `MistralDriver`, `GeminiDriver`, `OllamaDriver`, `OpenRouterDriver`. API keys are decrypted from the `llm_providers` table at call time.

When a user runs a prompt, the `PromptRunController` creates a `prompt_runs` record, then dispatches the rendered prompt to each selected provider. Responses are stored as `llm_responses` rows linked to the run.

---

## Deployment Notes (Shared Hosting)

- Point the domain document root to the `public/` directory
- Set `DB_DATABASE` to the **absolute path** of `storage/app/database.sqlite`, using forward slashes and wrapping in quotes in `.env` (the `#` character in paths must be quoted)
- The `APP_KEY` backs both session encryption and the `key_encrypted` column — back it up; rotating it invalidates all stored API key ciphertexts
- Run `npm run build` locally and deploy `public/build/` alongside the rest of the project
- Set `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true` in production
- Artisan commands for production setup:
  ```bash
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

### Known Windows/OneDrive quirk (dev only)
PHP's `is_writable()` returns `false` on OneDrive-backed paths even when writes succeed. Two vendor files were patched to use a write-test fallback instead:
- `vendor/laravel/framework/src/Illuminate/Foundation/PackageManifest.php`
- `vendor/laravel/framework/src/Illuminate/Foundation/ProviderRepository.php`

These patches are **dev-only** and will be overwritten by `composer update`. They are not needed on a normal hosting environment.
