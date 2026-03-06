# URGE — API Reference

## Base URL

```
https://your-domain.com/api/v1
```

All API routes are prefixed with `/api/v1/`.

---

## Authentication

Every request must include a valid API key as a Bearer token:

```
Authorization: Bearer urge_your_api_key_here
```

API keys are created and managed through the web UI under **API Keys**.

### Error responses for authentication failures

| HTTP | Code | Meaning |
|---|---|---|
| 401 | `MISSING_API_KEY` | No `Authorization` header present |
| 401 | `INVALID_API_KEY_FORMAT` | Header present but not in `Bearer <token>` format |
| 401 | `INVALID_API_KEY` | Token not found in the database |
| 401 | `EXPIRED_API_KEY` | Key exists but its expiry date has passed |
| 401 | `KEY_OWNER_NOT_FOUND` | The user who owned this key has been deleted |
| 403 | `KEY_SCOPE_DENIED` | The API key is scoped and does not have access to the requested prompt |

### Prompt-scoped API keys

API keys can optionally be scoped to specific prompts. A scoped key can only access the prompts it has been granted. Accessing any other prompt returns `403 KEY_SCOPE_DENIED`. An unscoped key (no prompts assigned) can access all prompts.

---

## Health Check

A public endpoint that requires no authentication:

```
GET /api/v1/health
```

**Response 200** — database is reachable:

```json
{
  "status": "ok",
  "timestamp": "2026-03-06T12:00:00.000000Z",
  "database": true
}
```

**Response 503** — database is unreachable:

```json
{
  "status": "error",
  "timestamp": "2026-03-06T12:00:00.000000Z",
  "database": false
}
```

No `Authorization` header is required. This endpoint is not rate limited.

---

## Rate Limiting

All authenticated API endpoints are rate limited per API key. The default limit is **60 requests per 60 seconds**.

When the limit is exceeded, the API returns HTTP `429`:

```json
{
  "error": {
    "code": "RATE_LIMITED",
    "message": "Too many requests. Try again in 42 seconds."
  }
}
```

### Rate limit headers

| Header | Description |
|---|---|
| `Retry-After` | Seconds until the rate limit resets |
| `X-RateLimit-Remaining` | Number of requests remaining in the current window |

Rate limits are tracked independently per API key. Two different API keys each get their own quota.

The rate limit can be configured via environment variables:

| Variable | Default | Description |
|---|---|---|
| `URGE_API_RATE_LIMIT` | `60` | Maximum requests per window |
| `URGE_API_RATE_WINDOW` | `60` | Window duration in seconds |

---

## Error Response Format

All errors follow this shape:

```json
{
  "error": {
    "code": "NOT_FOUND",
    "message": "No prompt with slug 'my-prompt' was found."
  }
}
```

### HTTP status codes used

| Status | Meaning |
|---|---|
| 200 | Success |
| 401 | Unauthenticated |
| 404 | Resource not found |
| 403 | Forbidden (scoped key) |
| 422 | Validation error |
| 429 | Rate limit exceeded |
| 500 | Server error |
| 503 | Service unavailable (health check) |

---

## Endpoints

### List prompts

Returns all prompts that have an active version set. Archived (soft-deleted) prompts are excluded. Results are cursor-paginated.

```
GET /api/v1/prompts
```

| Parameter | Type | Default | Description |
|---|---|---|---|
| `per_page` | integer | `25` | Number of results per page (1–100) |
| `cursor` | string | — | Cursor token for the next page |

If the API key is scoped to specific prompts, only those prompts are returned.

**Response 200**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Support Reply",
      "slug": "support-reply",
      "description": "Generates a polite support response.",
      "active_version": 2,
      "variables": ["customer_name", "issue"],
      "tags": ["support", "customer"],
      "variable_metadata": {
        "customer_name": {"type": "string", "description": "Customer's full name", "default": null},
        "issue": {"type": "string", "description": "The issue description", "default": null}
      },
      "includes": [],
      "created_at": "2026-03-05T08:34:35.000000Z"
    }
  ],
  "meta": {
    "per_page": 25,
    "next_cursor": "eyJpZCI6MjUsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0",
    "prev_cursor": null
  }
}
```

To fetch all pages, follow `meta.next_cursor` until it is `null`:

```bash
# First page
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts?per_page=100"

# Next page (if next_cursor is not null)
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts?per_page=100&cursor=$NEXT_CURSOR"
```

---

### Get a prompt

Returns the active version of a prompt by slug.

```
GET /api/v1/prompts/{slug}
```

| Parameter | Type | Description |
|---|---|---|
| `slug` | string | The URL-safe identifier of the prompt |

**Response 200**

```json
{
  "data": {
    "id": 1,
    "name": "Support Reply",
    "slug": "support-reply",
    "description": "Generates a polite support response.",
    "version": {
      "id": 3,
      "version_number": 2,
      "content": "Dear {{customer_name}}, thank you for contacting us about {{issue}}.",
      "commit_message": "Softened tone",
      "variables": ["customer_name", "issue"],
      "variable_metadata": {
        "customer_name": {"type": "string", "description": "Customer's full name", "default": null},
        "issue": {"type": "string", "description": "The issue description", "default": null}
      },
      "includes": [],
      "created_by": "Alice",
      "created_at": "2026-03-05T09:00:00.000000Z"
    }
  }
}
```

**Response 404** — prompt not found, has no active version, or has been archived.

---

### List versions of a prompt

Returns the full version history of a prompt, newest first.

```
GET /api/v1/prompts/{slug}/versions
```

**Response 200**

```json
{
  "data": [
    {
      "version_number": 2,
      "commit_message": "Softened tone",
      "variables": ["customer_name", "issue"],
      "variable_metadata": {
        "customer_name": {"type": "string", "description": "Customer's full name", "default": null},
        "issue": {"type": "string", "description": "The issue description", "default": null}
      },
      "includes": [],
      "created_by": "Alice",
      "created_at": "2026-03-05T09:00:00.000000Z",
      "is_active": true
    },
    {
      "version_number": 1,
      "commit_message": "Initial version",
      "variables": ["customer_name", "issue"],
      "variable_metadata": null,
      "created_by": "Alice",
      "created_at": "2026-03-05T08:34:35.000000Z",
      "is_active": false
    }
  ]
}
```

**Response 404** — prompt not found.

---

### Get a specific version

Returns a specific version by its version number (not its database ID).

```
GET /api/v1/prompts/{slug}/versions/{version_number}
```

| Parameter | Type | Description |
|---|---|---|
| `slug` | string | Prompt slug |
| `version_number` | integer | Version number (e.g. `1`, `2`, `3`) |

**Response 200**

```json
{
  "data": {
    "id": 1,
    "name": "Support Reply",
    "slug": "support-reply",
    "description": "Generates a polite support response.",
    "version": {
      "id": 2,
      "version_number": 1,
      "content": "Dear {{customer_name}}, thank you for reaching out about {{issue}}.",
      "commit_message": "Initial version",
      "variables": ["customer_name", "issue"],
      "variable_metadata": {
        "customer_name": {"type": "string", "description": "Customer's full name", "default": null},
        "issue": {"type": "string", "description": "The issue description", "default": null}
      },
      "includes": [],
      "created_by": "Alice",
      "created_at": "2026-03-05T08:34:35.000000Z",
      "is_active": false
    }
  }
}
```

**Response 404** — prompt or version not found.

---

### Render a prompt

Substitutes `{{variable}}` placeholders with provided values and returns the rendered text. This is the primary endpoint for LLM pipeline consumers.

```
POST /api/v1/prompts/{slug}/render
Content-Type: application/json
```

**Request body**

```json
{
  "variables": {
    "customer_name": "Alice",
    "issue": "billing discrepancy"
  },
  "version": 2
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `variables` | object | No | Key-value pairs to substitute into the prompt |
| `version` | integer | No | Specific version number to render. Takes precedence over `environment`. Defaults to the active version. |
| `environment` | string | No | Named environment (e.g. `staging`, `production`) whose assigned version should be rendered. Ignored if `version` is also set. |

**Response 200**

```json
{
  "data": {
    "rendered": "Dear Alice, thank you for contacting us about billing discrepancy.",
    "prompt_slug": "support-reply",
    "version_number": 2,
    "variables_used": ["customer_name", "issue"],
    "variables_missing": []
  }
}
```

**Variable defaults**

If a variable has metadata with a `default` value and the variable is not provided in the request, the default value is used automatically. The variable will appear in `variables_used`, not `variables_missing`.

**Environment resolution**

The version to render is resolved with this priority:
1. Explicit `version` number (if provided)
2. `environment` name → the version assigned to that environment
3. The prompt's active version

If an `environment` is specified but does not exist for the prompt, the API returns `404` with code `ENVIRONMENT_NOT_FOUND`.

**Behaviour for missing variables**

If a variable is present in the prompt content but not provided in the request and has no default, the placeholder is **left unreplaced** in the rendered output. The missing variable names are listed in `variables_missing`. The response is still `200` — it is up to the consumer to decide how to handle gaps.

```json
{
  "data": {
    "rendered": "Dear Alice, thank you for contacting us about {{issue}}.",
    "prompt_slug": "support-reply",
    "version_number": 2,
    "variables_used": ["customer_name"],
    "variables_missing": ["issue"]
  }
}
```

**Response 404** — prompt or specified version not found.

---

## Variable Syntax

Variables in prompt content use double-curly-brace syntax:

```
{{variable_name}}
```

Rules:
- Must start with a letter or underscore: `[a-zA-Z_]`
- Followed by letters, digits, or underscores: `[a-zA-Z0-9_]*`
- Valid: `{{name}}`, `{{customer_id}}`, `{{_tone}}`
- Invalid (silently ignored): `{{123}}`, `{{has space}}`, `{{has-hyphen}}`

---

## Prompt Composition (Includes)

Prompts can include other prompts using the include syntax:

```
{{>slug}}
```

When a prompt containing `{{>slug}}` is rendered, the include tag is replaced with the active version content of the referenced prompt. Includes are resolved **before** variable substitution, so variables from included prompts are available for replacement.

### Rules

- `slug` must match a valid prompt slug: `[a-zA-Z0-9_-]+`
- Includes are **recursive** — an included prompt can itself include other prompts
- **Circular references** are detected and return `422 INCLUDE_ERROR`
- **Max depth** defaults to 10 (configurable via `URGE_MAX_INCLUDE_DEPTH`)
- If an included slug does not exist or has no active version, the tag is left as-is in the output
- **Environment propagation** — if you render with `"environment": "staging"`, included prompts also use their `staging` version (if available, otherwise their active version)
- Variables are **shared** across all levels — a variable passed to the parent prompt is available in all included content

### Example

Given three prompts:

- `system-rules` (active content): `You are a helpful assistant. Always be polite.`
- `tone-guide` (active content): `Respond in a {{tone}} tone.`
- `support-reply` (active content):

```
{{>system-rules}}
{{>tone-guide}}

Dear {{customer_name}}, thank you for contacting us about {{issue}}.
```

Rendering `support-reply` with `{"customer_name": "Alice", "issue": "billing", "tone": "friendly"}` produces:

```
You are a helpful assistant. Always be polite.
Respond in a friendly tone.

Dear Alice, thank you for contacting us about billing.
```

### Render response with includes

When includes are resolved, the response includes an `includes_resolved` field listing all slugs that were expanded:

```json
{
  "data": {
    "rendered": "You are a helpful assistant. Always be polite.\nRespond in a friendly tone.\n\nDear Alice, thank you for contacting us about billing.",
    "prompt_slug": "support-reply",
    "version_number": 1,
    "variables_used": ["customer_name", "issue", "tone"],
    "variables_missing": [],
    "includes_resolved": ["system-rules", "tone-guide"]
  }
}
```

---

## Usage Examples

### Fetch and render a prompt (curl)

```bash
curl -X POST https://your-domain.com/api/v1/prompts/support-reply/render \
  -H "Authorization: Bearer urge_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "variables": {
      "customer_name": "Alice",
      "issue": "billing discrepancy"
    }
  }'
```

### Fetch a prompt in Python

```python
import requests

BASE = "https://your-domain.com/api/v1"
HEADERS = {"Authorization": "Bearer urge_your_api_key"}

# Get the active version content
prompt = requests.get(f"{BASE}/prompts/support-reply", headers=HEADERS).json()
content = prompt["data"]["version"]["content"]

# Or render server-side
result = requests.post(
    f"{BASE}/prompts/support-reply/render",
    headers=HEADERS,
    json={"variables": {"customer_name": "Alice", "issue": "billing discrepancy"}}
).json()

rendered_text = result["data"]["rendered"]
```

### Fetch a prompt in JavaScript / Node.js

```javascript
const BASE = 'https://your-domain.com/api/v1';
const HEADERS = { Authorization: 'Bearer urge_your_api_key' };

const res = await fetch(`${BASE}/prompts/support-reply/render`, {
  method: 'POST',
  headers: { ...HEADERS, 'Content-Type': 'application/json' },
  body: JSON.stringify({
    variables: { customer_name: 'Alice', issue: 'billing discrepancy' }
  })
});

const { data } = await res.json();
console.log(data.rendered);
```

### Pin to a specific version

To ensure your application always uses a specific version regardless of what the active version is set to in the UI:

```bash
curl -X POST https://your-domain.com/api/v1/prompts/support-reply/render \
  -H "Authorization: Bearer urge_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"variables": {"customer_name": "Alice", "issue": "billing"}, "version": 1}'
```

---

## Notes

- The API is **read-only**. Creating or editing prompts is done through the web UI only.
- API keys inherit the role of their owner. All roles (admin, editor, viewer) have identical API access.
- API keys may be **scoped to specific prompts**. A scoped key receives `403 KEY_SCOPE_DENIED` when accessing prompts outside its scope.
- The list endpoint uses **cursor-based pagination**. Follow `meta.next_cursor` to iterate through all pages.
- **Variable metadata** (type, description, default) is included in prompt and version responses when present. Defaults are applied automatically during rendering.
- **Environments** allow different named stages (e.g. `production`, `staging`) to point to different versions. Use the `environment` field in the render request.
- **Prompt composition** via `{{>slug}}` allows including one prompt's content inside another. Includes are resolved recursively during rendering. Circular references return `422 INCLUDE_ERROR`.
- **Archived prompts** (soft-deleted via the web UI) are invisible to all API endpoints. Fetching an archived prompt by slug returns `404`. Admins can restore archived prompts through the web UI.
