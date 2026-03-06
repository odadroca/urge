# URGE

A self-hosted prompt management system. Create, version-control, and publish LLM prompts through a web UI, then retrieve them from any application via a REST API.

---

## Features

- **Prompt CRUD** — create and edit prompts with descriptions and auto-generated slugs
- **Immutable version history** — every edit creates a new numbered version; old versions are never modified or deleted
- **Variable templating** — use `{{variable_name}}` placeholders; variables are extracted automatically and can be filled at render time via the API
- **Active version control** — explicitly promote any version to be the one the API serves by default
- **REST API** — read-only API with Bearer token authentication for use in LLM pipelines
- **Multi-user with roles** — admin, editor, and viewer roles; first registered user becomes admin
- **API key management** — per-user keys with optional expiry; keys are stored encrypted
- **Prompt testing** — execute prompts against multiple LLM providers (OpenAI, Anthropic, Mistral, Gemini, Ollama, OpenRouter) and compare responses side-by-side
- **Response library** — save, rate (1–5 stars), compare, and export LLM responses
- **Stories** — chain prompts and responses into ordered, multi-step sequences
- **Tags** — organize prompts with tags; filter and browse by tag
- **Dashboard** — overview of prompts, runs, library entries, stories, and top tags at a glance

---

## Stack

- **PHP 8.3 / Laravel 12**
- **SQLite** (single file, zero server maintenance)
- **Blade + Alpine.js + Tailwind CSS** via Laravel Breeze

---

## Requirements

- PHP 8.3+ with extensions: `openssl`, `pdo_sqlite`, `mbstring`, `curl`, `fileinfo`
- Composer
- Node.js + npm (for building frontend assets)

---

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file and configure
cp .env.example .env

# 3. Set your database path in .env (use forward slashes, quote the value)
# DB_DATABASE="/absolute/path/to/storage/app/database.sqlite"

# 4. Generate application key
php artisan key:generate

# 5. Create the SQLite database file
touch storage/app/database.sqlite

# 6. Run migrations
php artisan migrate

# 7. Build frontend assets
npm install && npm run build
```

### Development server

```bash
php artisan serve
```

The app will be available at `http://127.0.0.1:8000`.

---

## First login

Go to `/register`. The **first user to register is automatically assigned the admin role**. All subsequent registrations default to `viewer`. Admins can create and manage users directly from the **Users** page without requiring self-registration.

---

## API usage

All API routes are under `/api/v1/` and require a Bearer token:

```
Authorization: Bearer urge_your_key_here
```

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/prompts` | List all prompts with an active version |
| GET | `/api/v1/prompts/{slug}` | Get the active version of a prompt |
| GET | `/api/v1/prompts/{slug}/versions` | List all versions of a prompt |
| GET | `/api/v1/prompts/{slug}/versions/{n}` | Get a specific version by number |
| POST | `/api/v1/prompts/{slug}/render` | Render a prompt with variable substitution |

### Render example

```bash
curl -X POST https://your-domain.com/api/v1/prompts/support-reply/render \
  -H "Authorization: Bearer urge_your_key" \
  -H "Content-Type: application/json" \
  -d '{"variables": {"customer_name": "Alice", "issue": "billing"}}'
```

```json
{
  "data": {
    "rendered": "Dear Alice, thank you for contacting us about billing.",
    "prompt_slug": "support-reply",
    "version_number": 2,
    "variables_used": ["customer_name", "issue"],
    "variables_missing": []
  }
}
```

See [`documentation/api-reference.md`](documentation/api-reference.md) for the full API reference.

---

## Roles

| Role | Capabilities |
|---|---|
| **admin** | Full access — prompts, versions, users, all API keys, LLM provider configuration |
| **editor** | Create and edit prompts, run prompts, manage library and stories, manage own API keys |
| **viewer** | Read-only access to prompts, run prompts, manage library and stories, manage own API keys |

---

## Deployment (shared hosting)

1. Point the domain document root to the `public/` directory
2. Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` in `.env`
3. Run `php artisan migrate --force && php artisan config:cache && php artisan route:cache`
4. Build assets locally with `npm run build` and upload `public/build/`
5. Back up your `APP_KEY` — it encrypts stored API key values

---

## Documentation

| File | Contents |
|---|---|
| [`documentation/architecture.md`](documentation/architecture.md) | Data model, file structure, auth flows, design decisions |
| [`documentation/api-reference.md`](documentation/api-reference.md) | Full API endpoint reference with examples |
| [`documentation/user-guide.md`](documentation/user-guide.md) | Non-technical walkthrough for web UI users |
| [`documentation/configuration-reference.md`](documentation/configuration-reference.md) | All environment variables and their defaults |
| [`documentation/claude_skill.md`](documentation/claude_skill.md) | Ready-to-install Claude Code skill for URGE API access |

---

## Claude Code skill

A Claude Code skill is included in `documentation/claude_skill.md`. To install it:

```bash
mkdir -p ~/.claude/skills/urge
cp documentation/claude_skill.md ~/.claude/skills/urge/SKILL.md
```

Then use `/urge list`, `/urge get <slug>`, `/urge render <slug>`, etc. from any Claude Code session.

---
