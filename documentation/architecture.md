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
        PromptVersionController.php  # Version history, create, activate
        ApiKeyController.php       # API key management
        UserController.php         # Admin: create, edit, delete users
      Auth/                        # Breeze-generated auth controllers
    Middleware/
      ApiKeyAuthentication.php     # Validates Bearer token on all API routes
      RequireRole.php              # Role gate for admin-only web routes
  Models/
    User.php                       # role column + isAdmin()/isEditor() helpers
    Prompt.php                     # slug auto-generation on create
    PromptVersion.php              # Immutable: updating() throws LogicException
    ApiKey.php                     # key_hash, key_encrypted, key_preview
  Policies/
    PromptPolicy.php               # view/create/update/delete/createVersion/activateVersion
    ApiKeyPolicy.php               # delete (owner or admin)
    UserPolicy.php                 # admin-only
  Services/
    TemplateEngine.php             # {{var}} extraction and substitution
    ApiKeyService.php              # Key generation, storage, lookup
    VersioningService.php          # Transactional version number assignment

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
| `active_version_id` | bigint NULLABLE FK → prompt_versions.id | Points to the published version |
| `created_by` | bigint FK → users.id | |
| `created_at / updated_at` | timestamp | |

The slug is generated once at creation time (`Str::slug($name)` with a numeric suffix if taken) and is **locked permanently**. This guarantees API consumers always use the same URL regardless of prompt renames.

---

### `prompt_versions`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `prompt_id` | bigint FK → prompts.id CASCADE | |
| `version_number` | unsigned int | Scoped per prompt (1, 2, 3…). Unique per prompt. |
| `content` | longtext | Prompt text with `{{variable}}` placeholders |
| `commit_message` | string(500) NULLABLE | |
| `variables` | json | Extracted variable names, auto-populated on save |
| `created_by` | bigint FK → users.id | |
| `created_at` | timestamp | **No `updated_at`** — versions are immutable |

**Immutability** is enforced at the model level: `PromptVersion::updating()` throws a `LogicException`. Once written, a version record cannot be changed.

**Version numbering** uses a database transaction with `MAX(version_number) + 1` scoped to `prompt_id`, ensuring no gaps or races (SQLite serialises writes at the table level).

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
| Manage own API keys | ✓ | ✓ | ✓ |
| Create users | ✓ | ✗ | ✗ |
| Edit user roles | ✓ | ✗ | ✗ |
| Delete users | ✓ | ✗ | ✗ |
| API access (all read + render) | ✓ | ✓ | ✓ |

Permissions are enforced via Laravel Policies registered in `AppServiceProvider`. The `RequireRole` middleware guards admin-only web routes.

---

## Template Engine

`App\Services\TemplateEngine` handles `{{variable}}` syntax.

- **Pattern:** `/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/`
- Variable names must start with a letter or underscore (rejects `{{123}}`, `{{has space}}`)
- `extractVariables(string $content): array` — returns unique variable names found in the content
- `render(string $content, array $variables): array` — substitutes provided values; leaves unmatched placeholders untouched and returns them in `variables_missing`

---

## API Key Generation

```
Format:  urge_<62 hex characters>
Length:  67 characters total
Source:  'urge_' . bin2hex(random_bytes(31))
```

`random_bytes()` pulls from the OS CSPRNG, ensuring cryptographic quality randomness.

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
