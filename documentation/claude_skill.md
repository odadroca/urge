---
name: urge
description: "Interacts with a URGE prompt management server. Use when the user wants to list, retrieve, search, or render prompts stored in URGE; or when they need to fetch a specific prompt version for use in a task."
argument-hint: "list | get <slug> | render <slug> [env=<name>] | versions <slug> | search <tag> | help"
---

# URGE Prompt Manager

You have access to a URGE server — a self-hosted prompt management system. You can list prompts, retrieve their content, browse version history, and render prompts with variable substitution.

## Configuration

Before making any API call, you need two values. Check environment variables first (`URGE_BASE_URL` and `URGE_API_KEY`), then check if the user has already provided them in this conversation. If neither source has them, ask for both at once.

- **URGE_BASE_URL** — the base URL of the URGE instance, e.g. `https://prompts.example.com`
- **URGE_API_KEY** — a Bearer API key starting with `urge_`

Users can persist these in environment variables or in `~/.urge.env` to avoid being prompted each session.

Store them for the duration of the session and never ask again once provided.

## How to make API calls

Use the Bash tool with curl. Replace `$BASE` and `$KEY` with the values provided by the user.

```bash
curl -s \
  -H "Authorization: Bearer $KEY" \
  -H "Accept: application/json" \
  "$BASE/api/v1/..."
```

Always parse the JSON response and present results in a readable, human-friendly format — not raw JSON — unless the user explicitly asks for raw output.

---

## Operations

When the user invokes `/urge` with no arguments, or says something like "show me my prompts" or "what prompts do I have", default to **list**.

### list — List all prompts

The list endpoint is paginated. Fetch all pages automatically by following `next_cursor`:

```bash
# First page
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts?per_page=100"
```

If `meta.next_cursor` is not null, fetch the next page:

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts?per_page=100&cursor=$NEXT_CURSOR"
```

Repeat until `next_cursor` is null. If the user passes `--limit N`, stop after collecting N prompts.

Present results as a table or list with: name, slug, tags, active version number, and variable names. If a prompt has no active version, mark it clearly.

---

### search `<tag>` — Search prompts by tag

Use the list operation and filter results client-side by the `tags` array field. Display only prompts whose tags include the search term.

---

### get `<slug>` — Get the active version of a prompt

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts/$SLUG"
```

Display:
- Name, slug, description
- Active version number and commit message
- Variables as `{{name}}` badges
- If `variable_metadata` is present, show each variable's description and default value
- Full prompt content in a code block

If the user says "give me the X prompt" or "fetch prompt X", use this operation.

---

### get `<slug>` `<version>` — Get a specific version

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts/$SLUG/versions/$VERSION"
```

Same display as above, noting whether this version is the active one. Include `variable_metadata` if present.

---

### versions `<slug>` — List all versions of a prompt

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts/$SLUG/versions"
```

Present as a table: version number, whether active, commit message, author, date.

---

### render `<slug>` — Render a prompt with variables

First, fetch the prompt to discover its variables:

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts/$SLUG"
```

Then, if the user has not yet provided values for all variables, ask for them in a single message listing all required variables. If `variable_metadata` includes descriptions or defaults, show them to help the user fill in values. Variables with defaults will be applied automatically if the user does not provide a value.

Once you have the values, render:

```bash
curl -s -X POST \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"variables": { ... }}' \
  "$BASE/api/v1/prompts/$SLUG/render"
```

Display the `rendered` text prominently. If `variables_missing` is non-empty, warn the user which placeholders were not filled and show the rendered text with the unreplaced `{{placeholder}}` still visible. If `includes_resolved` is present in the response, mention which prompts were included.

If the user says "render X with name=Alice and issue=billing", extract the variables from the message and skip asking.

---

### render `<slug>` env=`<name>` — Render using a specific environment

Pass `"environment": "<name>"` in the request body to render using the version assigned to that environment (e.g. `staging`, `production`):

```bash
curl -s -X POST \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"variables": { ... }, "environment": "staging"}' \
  "$BASE/api/v1/prompts/$SLUG/render"
```

If the environment does not exist for this prompt, the API returns 404 with code `ENVIRONMENT_NOT_FOUND`.

---

### render `<slug>` at version `<N>`

Same as render, but pass `"version": N` in the request body. Version pinning takes precedence over environment.

```bash
-d '{"variables": { ... }, "version": N}'
```

---

## Error handling

| HTTP | Code | What to tell the user |
|---|---|---|
| 401 `MISSING_API_KEY` | No key was sent — check configuration |
| 401 `INVALID_API_KEY` | The key is wrong or has been revoked |
| 401 `EXPIRED_API_KEY` | The key has expired — generate a new one in the URGE web UI |
| 403 `KEY_SCOPE_DENIED` | The API key does not have access to this prompt — the key may be scoped to specific prompts |
| 404 `NOT_FOUND` | The prompt slug or version number does not exist |
| 422 `INCLUDE_ERROR` | Circular include detected or max include depth exceeded |
| 404 `ENVIRONMENT_NOT_FOUND` | The specified environment does not exist for this prompt |
| 429 `RATE_LIMITED` | Too many requests — wait and retry after the `Retry-After` header value |

For any 5xx error, suggest the user check whether the URGE server is running.

---

## Template syntax reminder

Prompts use two special syntaxes:

- **Variables**: `{{variable_name}}` — placeholders replaced with provided values. Names contain only letters, digits, and underscores, starting with a letter or underscore.
- **Includes**: `{{>slug}}` — includes the active content of another prompt by its slug. Includes are resolved recursively before variable substitution, so variables from included prompts are available.

Variables may have metadata (type, default, description) attached by prompt authors. When displaying a prompt, show this metadata to help the user understand what each variable expects. If a prompt has `includes`, mention which prompts it references.

---

## Additional web-only features

The following features are available in the URGE web interface but **not through the API**:

- **Runs** — Execute prompts against LLM providers directly from the UI, with response comparison and rating.
- **Response Library** — Save and compare LLM responses across runs.
- **Stories** — Multi-step prompt workflows that chain prompts together.

---

## Example interactions

**User:** `/urge list`
→ Fetch and display all prompts (follow pagination automatically).

**User:** `/urge search customer`
→ List prompts tagged with "customer".

**User:** `/urge get support-reply`
→ Fetch and display the active version of `support-reply`, including variable descriptions and defaults.

**User:** `/urge render support-reply`
→ Fetch the prompt, list its variables (with descriptions/defaults from metadata), ask the user for values, then render and display.

**User:** `/urge render support-reply` with `customer_name=Alice issue=billing`
→ Render immediately without asking.

**User:** `/urge render support-reply env=staging`
→ Render using the staging environment's version.

**User:** `/urge versions support-reply`
→ Display full version history of `support-reply`.

**User:** `/urge get support-reply 1`
→ Display version 1 of `support-reply`.

**User:** "fetch my onboarding prompt and use it"
→ Infer `get onboarding` (or ask to clarify the slug), retrieve the content, then use it directly in your response for whatever the user needs.

---

## Notes

- The URGE API is **read-only**. You cannot create, edit, or delete prompts through the API.
- To manage prompts, the user must use the URGE web interface.
- All roles (admin, editor, viewer) have the same API access level.
- API keys may be scoped to specific prompts — if you get a 403 `KEY_SCOPE_DENIED`, the key only has access to a subset of prompts.
- Archived (soft-deleted) prompts return 404 via the API. They can be restored by admins in the web UI.

---

## Setup instructions (for the user)

To install this skill, save this file as:

```
~/.claude/skills/urge/SKILL.md
```

Then invoke it with `/urge` from any Claude Code session.

You will need:
1. The URL of your URGE instance
2. An API key generated from **API Keys → New API Key** in the URGE web UI

For convenience, you can set environment variables to avoid being prompted each time:

```bash
export URGE_BASE_URL=https://prompts.example.com
export URGE_API_KEY=urge_your_key_here
```

Or create `~/.urge.env` with these values.
