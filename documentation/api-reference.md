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
| 422 | Validation error |
| 500 | Server error |

---

## Endpoints

### List prompts

Returns all prompts that have an active version set.

```
GET /api/v1/prompts
```

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
      "variables": ["customer_name", "issue", "product"],
      "created_at": "2026-03-05T08:34:35.000000Z"
    }
  ]
}
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
      "created_by": "Alice",
      "created_at": "2026-03-05T09:00:00.000000Z"
    }
  }
}
```

**Response 404** — prompt not found or has no active version.

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
      "created_by": "Alice",
      "created_at": "2026-03-05T09:00:00.000000Z",
      "is_active": true
    },
    {
      "version_number": 1,
      "commit_message": "Initial version",
      "variables": ["customer_name", "issue"],
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
| `version` | integer | No | Specific version number to render. Defaults to the active version. |

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

**Behaviour for missing variables**

If a variable is present in the prompt content but not provided in the request, the placeholder is **left unreplaced** in the rendered output. The missing variable names are listed in `variables_missing`. The response is still `200` — it is up to the consumer to decide how to handle gaps.

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
- The API does not implement pagination. All prompts are returned in a single response.
