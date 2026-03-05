---
name: urge
description: "Interacts with a URGE prompt management server. Use when the user wants to list, retrieve, search, or render prompts stored in URGE; or when they need to fetch a specific prompt version for use in a task."
argument-hint: "list | get <slug> | render <slug> | versions <slug> | help"
---

# URGE Prompt Manager

You have access to a URGE server — a self-hosted prompt management system. You can list prompts, retrieve their content, browse version history, and render prompts with variable substitution.

## Configuration

Before making any API call, you need two values. Check if the user has already provided them in this conversation. If not, ask for both at once:

- **URGE_BASE_URL** — the base URL of the URGE instance, e.g. `https://prompts.example.com`
- **URGE_API_KEY** — a Bearer API key starting with `urge_`

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

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts"
```

Present results as a table or list with: name, slug, active version number, and variable names. If a prompt has no active version, mark it clearly.

---

### get `<slug>` — Get the active version of a prompt

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts/$SLUG"
```

Display:
- Name, slug, description
- Active version number and commit message
- Variables as `{{name}}` badges
- Full prompt content in a code block

If the user says "give me the X prompt" or "fetch prompt X", use this operation.

---

### get `<slug>` `<version>` — Get a specific version

```bash
curl -s -H "Authorization: Bearer $KEY" "$BASE/api/v1/prompts/$SLUG/versions/$VERSION"
```

Same display as above, noting whether this version is the active one.

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

Then, if the user has not yet provided values for all variables, ask for them in a single message listing all required variables.

Once you have the values, render:

```bash
curl -s -X POST \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{"variables": { ... }}' \
  "$BASE/api/v1/prompts/$SLUG/render"
```

Display the `rendered` text prominently. If `variables_missing` is non-empty, warn the user which placeholders were not filled and show the rendered text with the unreplaced `{{placeholder}}` still visible.

If the user says "render X with name=Alice and issue=billing", extract the variables from the message and skip asking.

---

### render `<slug>` at version `<N>`

Same as render, but pass `"version": N` in the request body:

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
| 404 `NOT_FOUND` | The prompt slug or version number does not exist |

For any 5xx error, suggest the user check whether the URGE server is running.

---

## Variable syntax reminder

Prompts use `{{variable_name}}` placeholders. Variable names contain only letters, digits, and underscores, starting with a letter or underscore.

---

## Example interactions

**User:** `/urge list`
→ Fetch and display all prompts.

**User:** `/urge get support-reply`
→ Fetch and display the active version of `support-reply`.

**User:** `/urge render support-reply`
→ Fetch the prompt, list its variables, ask the user for values, then render and display.

**User:** `/urge render support-reply` with `customer_name=Alice issue=billing`
→ Render immediately without asking.

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
