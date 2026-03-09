# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

URGE is a self-hosted prompt management system for LLM developers. It manages prompts with immutable version history, variable templating (`{{var}}` and `{{>slug}}` includes), a REST API, multi-LLM testing, a response library, and story workflows.

**Stack:** Laravel 12 / PHP 8.3, SQLite, Blade, Alpine.js, Tailwind CSS, Vite

## Build & Dev Commands

```bash
composer dev          # Full dev stack: artisan serve + queue + logs + Vite
composer test         # Run PHPUnit tests
npm run dev           # Vite dev server with HMR
npm run build         # Production build (MUST run from C:/#DATA/Onedrive/Apps/URGE, not U:\urge)
php artisan migrate   # Run database migrations
php artisan serve     # Dev server at http://127.0.0.1:8000
```

**Critical:** Running `npm run build` from the mapped `U:\urge` drive produces absolute paths in `public/build/manifest.json`, breaking Vite. Always build from the canonical path.

## Architecture

### Core Data Flow

```
Prompt → PromptVersion[] (immutable, numbered) → PromptRun[] → LlmResponse[]
```

- **Prompts** have auto-generated slugs (locked after creation), soft deletes, optional categories and tags
- **Versions** are immutable (model-level enforcement via `booted()` throwing `LogicException` on update)
- **Active version** pointer on prompt; environment-specific overrides via `prompt_environments`
- **Runs** record execution; each run produces one `LlmResponse` per selected provider

### Template Engine (`App\Services\TemplateEngine`)

- `{{variable_name}}` — variable placeholder (letter/underscore start, alphanumeric)
- `{{>slug}}` — include another prompt's active version content
- Resolves includes recursively with circular reference detection
- Max depth: `URGE_MAX_INCLUDE_DEPTH` env (default 10)
- `render()` returns: rendered content, variables used/missing, includes resolved

### LLM Drivers (`App\Services\LlmProviders\`)

All implement `LlmDriverInterface::complete(string $prompt): LlmResult`. Drivers: OpenAI, Anthropic, Mistral, Gemini, Ollama, OpenRouter. OpenRouter uses raw cURL (not Guzzle) due to header stripping issues. All respect `config('urge.curl_ssl_verify')`.

### API Layer (`routes/api.php`)

Prefix `/api/v1/`, authenticated via `ApiKeyAuthentication` middleware (Bearer token → SHA-256 hash lookup). Keys can be scoped to specific prompts via pivot table. Rate limited per key.

### Web Layer (`routes/web.php`)

Session auth via Breeze. Roles: admin (full access), editor (create/edit), viewer (read + run). First registered user becomes admin. Role enforcement via `PromptPolicy` and `RequireRole` middleware (`role:admin`).

### Variable Metadata

Stored as JSON on `prompt_versions.variable_metadata`. Types: string, text, enum, number, boolean. Enum type supports inline options arrays.

## Key Patterns

- **Blade/Alpine `{{` conflict:** Use `'{' + '{'` string splitting in JS contexts within Blade templates to avoid parse errors
- **Auto-slug generation:** Used by Prompt and Category models — generates from name in `booted()` with collision counter
- **Tailwind dynamic classes:** Category/enum colors are database-driven; all color variants are safelisted in `tailwind.config.js`
- **Admin routes:** Wrapped in `middleware(['role:admin'])` group in `routes/web.php`
- **Config:** App-specific settings in `config/urge.php`, all env-driven

## Key Directories

- `app/Services/` — TemplateEngine, ApiKeyService, LlmDispatchService, LLM drivers
- `app/Policies/` — PromptPolicy, ApiKeyPolicy, UserPolicy
- `app/Http/Controllers/Api/` — REST API (extends ApiController for JSON helpers)
- `app/Http/Controllers/Web/` — Web UI controllers
- `documentation/` — Architecture, API reference, user guide, config reference
