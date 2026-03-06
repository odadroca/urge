# URGE — Configuration Reference

This document lists all environment variables recognised by URGE, their defaults, and their effect.

---

## Standard Laravel variables

These are standard Laravel configuration variables that affect URGE in deployment.

| Variable | Default | Notes |
|---|---|---|
| `APP_KEY` | *(none)* | **Required.** Application encryption key. Generated once with `php artisan key:generate`. Backs both session encryption and the `key_encrypted` column in `api_keys`. **Back this up — rotating it invalidates all stored API key ciphertexts.** |
| `APP_ENV` | `local` | Set to `production` in live deployments. |
| `APP_DEBUG` | `true` | Set to `false` in production to suppress stack traces in error pages. |
| `APP_URL` | `http://localhost` | The public URL of the application. |
| `DB_CONNECTION` | `sqlite` | URGE only uses SQLite. |
| `DB_DATABASE` | *(Laravel default)* | **Absolute path** to the SQLite database file. Use forward slashes. Quote the value in `.env` if the path contains spaces or `#`. Example: `DB_DATABASE="/var/www/urge/storage/app/database.sqlite"` |
| `SESSION_SECURE_COOKIE` | `false` | Set to `true` in production (requires HTTPS). |

---

## URGE-specific variables

All URGE-specific variables are defined in `config/urge.php` and can be overridden via `.env`.

### API key format

| Variable | Default | Description |
|---|---|---|
| `URGE_KEY_PREFIX` | `urge_` | Prefix prepended to every generated API key. Changing this does not affect existing keys. |
| `URGE_KEY_BYTES` | `31` | Number of random bytes used to generate the key body. The raw key is `URGE_KEY_PREFIX` + `bin2hex(random_bytes(URGE_KEY_BYTES))`. With the default prefix and 31 bytes, keys are 67 characters long. |

### API rate limiting

| Variable | Default | Description |
|---|---|---|
| `URGE_API_RATE_LIMIT` | `60` | Maximum number of API requests allowed per window per API key. |
| `URGE_API_RATE_WINDOW` | `60` | Duration of the rate limit window, in seconds. |

The effective limit is `URGE_API_RATE_LIMIT` requests per `URGE_API_RATE_WINDOW` seconds, tracked independently per API key.

### API key rotation

| Variable | Default | Description |
|---|---|---|
| `URGE_KEY_ROTATION_OVERLAP_HOURS` | `24` | When a key is rotated, the old key is set to expire this many hours in the future. This gives consuming applications time to update their credentials without a hard cutover. |

### Prompt composition

| Variable | Default | Description |
|---|---|---|
| `URGE_MAX_INCLUDE_DEPTH` | `10` | Maximum number of nested `{{>slug}}` include levels allowed during rendering. Prevents runaway recursion. Exceeding this depth returns `422 INCLUDE_ERROR`. |

---

## Minimal production `.env`

The following is the minimum recommended configuration for a production deployment:

```env
APP_NAME=URGE
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=sqlite
DB_DATABASE="/absolute/path/to/storage/app/database.sqlite"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Optional — override defaults as needed:
# URGE_API_RATE_LIMIT=60
# URGE_API_RATE_WINDOW=60
# URGE_KEY_ROTATION_OVERLAP_HOURS=24
# URGE_MAX_INCLUDE_DEPTH=10
```

---

## API Explorer tools (redist/)

The `redist/` directory contains **standalone HTML files** — self-contained browser-based API explorer tools that can be opened directly without a build step or server. They connect to a running URGE instance via the public API.

| File | Description |
|---|---|
| `redist/urge-explorer_v2.html` | API explorer v2 |
| `redist/urge-explorer_v3.html` | API explorer v3 (latest) |

To use the explorer:

1. Open `redist/urge-explorer_v3.html` directly in a browser (e.g. `file://…/urge/redist/urge-explorer_v3.html`), or serve it as a static file.
2. Enter your URGE base URL and an API key when prompted.
3. Browse and render prompts interactively from the UI.

These files are redistributable — you can share them with API consumers without needing to deploy anything extra.
