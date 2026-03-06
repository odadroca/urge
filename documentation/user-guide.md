# URGE — User Guide

## What is URGE?

URGE is a self-hosted prompt management system. It lets you write, version-control, and publish LLM prompts in one place, and retrieve them from any application via a REST API. Think of it as a lightweight, private alternative to Langfuse's prompt management feature.

---

## Getting Started

### First login

Open the application in your browser and go to `/register`. The **first user to register automatically becomes an admin**. Subsequent registrations default to the `viewer` role — the admin can change roles later.

If you are not using the public registration flow, an admin can create users directly from the **Users** page.

---

## Roles

| Role | What they can do |
|---|---|
| **admin** | Everything: create/edit/delete prompts, manage users and roles, manage all API keys, configure LLM providers |
| **editor** | Create and edit prompts, create versions, set active versions, run prompts, manage library and stories, manage their own API keys |
| **viewer** | Read-only access to all prompts and versions, run prompts, manage library and stories, manage their own API keys |

Your current role is visible in the top navigation area.

---

## Managing Prompts

### Creating a prompt

1. Click **Prompts** in the navigation bar.
2. Click **New Prompt**.
3. Enter a name and optional description.
4. Click **Create Prompt**.

A URL-safe slug is generated automatically from the name (e.g. "Support Reply" → `support-reply`). The slug is **permanent** — it does not change if you later rename the prompt — so API consumers always use the same URL.

---

### The prompt list

The prompt list shows all prompts in a compact table. Click any row to expand it and see:
- All `{{variable}}` placeholders used in the active version
- The full prompt content (scrollable if long)
- The commit message of the active version

Click again to collapse.

---

### Viewing a prompt

Click the prompt name or the **View** link to open its detail page. Here you can see:
- The active version content and its variables
- A ready-to-use API code snippet
- Links to edit the prompt or browse its version history

---

### Editing prompt metadata

Click **Edit** on the prompt detail page to change the name or description. This does **not** create a new version — it only updates the display name and description. The slug is never changed.

---

### Archiving a prompt

Only admins can archive prompts. Click **Archive Prompt** in the danger zone on the prompt detail page. Archived prompts are hidden from the main list and invisible to the API (they return `404`).

### Viewing archived prompts (Admin only)

On the prompt list page, admins see a **Show archived** toggle. Enabling it reveals archived prompts in a dimmed style with a **Restore** button.

### Restoring an archived prompt

Click **Restore** on an archived prompt (visible in the list or on its detail page). The prompt and all its versions become active again.

### Permanently deleting a prompt

On an archived prompt's detail page, admins can click **Delete permanently** to remove the prompt and all its versions from the database forever. This cannot be undone.

---

## Version Control

Every change to prompt content is saved as a new, immutable version. Versions are numbered sequentially per prompt (v1, v2, v3…). Old versions are never modified or deleted.

### Creating a new version

1. Open a prompt and click **New Version**.
2. Write or paste the prompt content. Use `{{variable_name}}` for placeholders and `{{>slug}}` to include another prompt (see [Prompt Composition](#prompt-composition) below).
3. Add an optional commit message describing what changed.
4. Click **Save Version**.

Variables and includes are extracted automatically — you will see them listed as badges.

### Variable metadata

When creating a new version, you can add metadata to each detected variable:

- **Type** — a hint for consumers; valid values:
  - `string` — a short single-line text value
  - `text` — a longer multi-line text value
  - `number` — a numeric value
  - `boolean` — `true` or `false`
  - `enum` — one of a fixed set of options (specify the allowed values in the **Options** field)
- **Default** — a fallback value used automatically when the variable is not provided during rendering; if set, the variable will appear in `variables_used` (not `variables_missing`) even when omitted
- **Description** — a short explanation of what the variable expects
- **Options** — for `enum` type only: a list of allowed values shown as a dropdown in the UI and returned as `options` in the API response

Metadata is optional. If the previous version had metadata, it is pre-populated for convenience.

The new version is **not active** by default. It must be promoted explicitly.

### Setting the active version

The active version is what the API returns when no version number is specified. To change it:

1. Open **History** from the prompt list or detail page.
2. Find the version you want to promote.
3. Click **Set Active**.

You can also set a version as active from the version detail page.

### Browsing history

Click **History** on any prompt to see all versions in reverse chronological order, including who created each one, when, and what the commit message was. Click **View** to read the full content of any past version.

### Comparing versions

On the version history page, check the boxes next to any two versions and click **View diff** to see a side-by-side comparison of their content.

### Prompt Composition

Prompts can include the content of other prompts using the `{{>slug}}` syntax. This avoids repeating shared content (like system rules or tone guidelines) across multiple prompts.

**How it works:**

- Write `{{>my-other-prompt}}` anywhere in your prompt content.
- When the prompt is rendered (via the API), the include tag is replaced with the active version content of the referenced prompt.
- Includes are recursive — an included prompt can itself include other prompts.
- Variables are shared across all levels. For example, if a parent prompt passes `tone`, it is available in all included content.
- The version create form shows detected includes as green badges with clickable links to the included prompts.

**Example:**

Create a prompt called "System Rules" with slug `system-rules`:
```
You are a helpful assistant. Always be polite and concise.
```

Then reference it in another prompt:
```
{{>system-rules}}

Dear {{customer_name}}, here is the answer to your question about {{topic}}.
```

When rendered, the `{{>system-rules}}` tag is replaced with the full content of `system-rules`.

**Environment propagation:** When rendering with an environment (e.g. `staging`), included prompts also use their staging version if one is assigned.

**Circular references** are detected and will return an error. The maximum include depth is 10 by default (configurable via `URGE_MAX_INCLUDE_DEPTH`).

### Environments

Environments let you assign different versions to named stages (e.g. `production`, `staging`). This is useful when you want to test a new version in staging before promoting it to production.

**Managing environments:**

1. Open **History** for a prompt.
2. Scroll down to the **Environments** section (visible to editors and admins).
3. To change which version an environment points to, select a version from the dropdown and click **Assign**.
4. To create a new environment, type a name in the text field, select a version, and click **Add**.

The default environment suggestions (`production`, `staging`) appear in the dropdown. You can create any custom environment name.

**Using environments via the API:**

Pass `"environment": "staging"` in the render request body to render the version assigned to that environment instead of the active version. See the [API Reference](./api-reference.md) for details.

---

## API Keys

API keys allow external applications to query URGE programmatically. Each key is tied to a user and inherits that user's role.

### Creating an API key

1. Click **API Keys** in the navigation bar.
2. Click **New API Key**.
3. Give it a descriptive name (e.g. "Production App", "Dev Laptop").
4. Optionally set an expiry date.
5. Optionally scope the key to specific prompts by checking them in the **Prompt Scope** section. An unscoped key can access all prompts.
6. Click **Generate Key**.

**The full key is shown exactly once** on the next screen. Copy it immediately — it cannot be retrieved again from the UI. If you lose it, revoke it and generate a new one.

The key list shows a short preview (first 8 characters) and the last time the key was used, so you can identify and audit your keys.

### Prompt-scoped API keys

Scoped keys can only access the prompts they are assigned to. Attempting to access other prompts returns `403 KEY_SCOPE_DENIED`. The key list shows the scope as a badge — either "All prompts" or the number of scoped prompts.

### Rotating an API key

Click **Rotate** next to a key. This creates a new key with the same name, expiry, and prompt scope, and sets the old key to expire after a configurable overlap window (default: 24 hours). This gives you time to update applications without downtime.

The overlap window is configurable via the `URGE_KEY_ROTATION_OVERLAP_HOURS` environment variable.

### Revoking an API key

Click **Revoke** next to any key. This immediately invalidates it — any application using it will receive a `401 INVALID_API_KEY` error.

### Using your API key

Include it in the `Authorization` header of every API request:

```
Authorization: Bearer urge_your_key_here
```

See the [API Reference](./api-reference.md) for full endpoint documentation.

---

## User Management (Admin only)

### Creating a user

1. Click **Users** in the navigation bar (visible to admins only).
2. Click **New User**.
3. Fill in the name, email, password, and role.
4. Click **Create User**.

The new user can then log in with the credentials you provided. You may want to share the password securely and ask them to change it from their profile page.

### Changing a user's role

1. Go to **Users**.
2. Click **Edit** next to the user.
3. Select the new role and click **Save**.

### Deleting a user

Click **Delete** next to a user on the Users page. You cannot delete your own account. Deleting a user also deletes all of their API keys.

---

## Profile & Password

Click your name in the top-right corner and select **Profile** to:
- Update your display name and email address
- Change your password
- Delete your own account

---

## Dashboard

After logging in you land on the dashboard, which provides a quick overview of everything in URGE.

### Stat cards

Five summary cards are shown across the top:

- **Prompts** — total number of prompts
- **Active versions** — how many prompts have an active version (and what percentage)
- **Library entries** — total saved responses in the library
- **Stories** — total stories
- **Total runs** — total prompt executions across all prompts

### Recent activity

Below the stat cards you will find:

- **Recent Prompts** — the five most recently updated prompts with version badge and tags
- **Needs Attention** — prompts that have no active version yet, with a quick link to set one
- **Quick Actions** — shortcuts to create a new prompt, library entry, story, or API key
- **Recent Runs** — latest prompt executions with response count and creator
- **Recent Library** — latest library entries with provider, model, and rating
- **Top Tags** — the 12 most-used tags as clickable pills that filter the prompt list

---

## Tags

Prompts can be tagged to help with organisation and discovery.

### Adding tags

When creating or editing a prompt, enter tags as a comma-separated list (e.g. `onboarding, support, v2`). Tags are stored as a JSON array on the prompt.

### Using tags

- Tags appear as badges on prompts throughout the app.
- Click any tag badge (on a prompt detail page, the prompt list, or the dashboard) to filter the prompt list to only prompts with that tag.
- The dashboard shows the 12 most-used tags with counts.

---

## Running Prompts

URGE can send a prompt directly to one or more LLM providers and display the responses side-by-side. This is useful for testing prompt changes before publishing them via the API.

### Prerequisites

- The prompt must have an **active version** (or at least one version).
- At least one LLM provider must be **enabled** by an admin (see [LLM Providers](#llm-providers-admin-only) below).

### How to run a prompt

1. Open a prompt and click the green **Run** button.
2. If the prompt contains `{{variables}}`, fill in values for each one. Fields can be left blank to keep the placeholder in the rendered output.
3. A **Prompt Preview** section lets you expand and review the raw template before running.
4. Select which LLM providers to use (all enabled providers are checked by default).
5. Click **Run Prompt**.

### Viewing results

After the run completes you are taken to the results page:

- Each provider's response is shown as a card with the **provider name**, **model**, **status** (success or error), **duration**, and **token counts** (input/output).
- Response text is displayed in a scrollable monospace box.
- If a provider returned an error, the error message is shown in a red banner.

### Rating responses

Click the stars (1–5) on any response card to rate it. The rating is saved immediately via AJAX — no page reload needed.

### Exporting and saving

- **Export** — click the export button on a response card to download it as a `.md` file containing the prompt, variables, response text, and rating.
- **Save to Library** — click to save a successful response directly to the Response Library for future reference and comparison.

### Run history

Click **Runs** on a prompt's detail page to see a paginated table of all past executions. Each row shows the run ID, version used, models queried, number of ratings, creator, and date. Click **View** to revisit the results.

---

## LLM Providers (Admin only)

Admins can configure which LLM providers are available for prompt runs.

### Accessing provider settings

Navigate to **Admin → LLM Providers** (visible only to admin users).

### Pre-configured providers

URGE ships with these providers pre-seeded (all disabled by default):

| Provider | Default model |
|---|---|
| OpenAI GPT-4o Mini | `gpt-4o-mini` |
| Anthropic Claude Haiku | `claude-haiku-4-5-20251001` |
| Mistral Small | `mistral-small-latest` |
| Google Gemini Flash | `gemini-1.5-flash` |
| Ollama (local) | `llama3.2` |
| OpenRouter | `openai/gpt-4o-mini` |

### Configuring a provider

Click **Configure** next to any provider to open its settings:

- **Display Name** — the label shown in the run UI.
- **Model** — the model identifier sent to the provider's API (e.g. `gpt-4o`, `claude-sonnet-4-20250514`).
- **API Key** — enter the provider's API key. Keys are encrypted with AES-256-CBC before storage. Leave blank when editing to keep the existing key.
- **Base URL** — only shown for Ollama; defaults to `http://localhost:11434`.
- **Enabled** — toggle whether this provider appears in the run UI.

Ollama does not require an API key since it runs locally.

---

## Response Library

The library is a curated collection of saved LLM responses. Use it to bookmark interesting outputs, compare responses across providers, and build stories.

### Adding entries

There are two ways to add a library entry:

1. **From a run** — after running a prompt, click **Save to Library** on any successful response card. The entry is pre-filled with the prompt, version, provider, model, response text, and rating.
2. **Manually** — go to **Library → New Entry**, select a prompt and version, then enter the model name and response text yourself.

You can add optional **notes** (up to 2000 characters) to any entry.

### Browsing and filtering

The library index shows a paginated table of all entries. Use the filter bar to narrow results by:

- **Prompt** — show entries for a specific prompt only
- **Provider** — show entries from a specific LLM provider
- **Rated only** — show only entries that have a star rating

### Comparing responses

To compare multiple responses for the same prompt version:

1. Navigate to a library entry and click **Compare** (or use the compare link on the version detail page).
2. All library entries for that prompt version are displayed side-by-side (or stacked, using the toggle button).
3. Each card shows the provider, model, rating, word count, response text, and notes.

This is especially useful for evaluating how different models handle the same prompt.

### Editing and exporting

- Click **Edit** on any entry to update notes, rating, or response text.
- Click **Export** to download the entry as a markdown file.
- Click **Delete** to permanently remove an entry.

---

## Stories

Stories let you chain prompts and their responses into ordered, multi-step sequences. They are useful for documenting prompt workflows, building narrative threads, or planning multi-turn interactions.

### Creating a story

1. Click **Stories** in the navigation bar.
2. Click **New Story**.
3. Enter a title and optional description.
4. Click **Create & Add Steps** — you are taken to the edit page.

### Adding steps

Each step in a story links to a specific prompt version and optionally a library response:

1. On the story edit page, scroll to **Add Step**.
2. Select a **Prompt** from the dropdown.
3. Select a **Version** (versions for the chosen prompt are loaded automatically).
4. Optionally select a **Library Response** (library entries for that version are shown if any exist).
5. Add optional **Notes** for this step.
6. Click **Add Step**.

### Reordering steps

Use the **up/down arrow** buttons next to each step to change its position in the sequence. The first step cannot move up and the last step cannot move down.

### Viewing a story

The story detail page displays all steps in an expandable accordion. Each step header shows the step number, prompt name, version, and linked library response (if any). Click a step to expand it and see the full prompt content and response text.

### Deleting

- To remove a single step, click the **delete** button next to it on the edit page.
- To delete an entire story (and all its steps), use the **Delete Story** button in the danger zone at the bottom of the edit page.

---

## Version Compare

You can compare two versions of the same prompt side-by-side to see what changed.

1. Open **History** on any prompt.
2. Select two versions to compare (via the **Compare** link or by choosing version numbers).
3. The compare view shows the two versions side-by-side with differences highlighted.

---

## Tips

- **Use slugs as stable identifiers.** Because slugs never change, you can hard-code them in your application config and rename prompts freely in the UI without breaking anything.
- **Use commit messages.** Even brief notes like "Added product name variable" or "Adjusted tone" make version history much easier to navigate later.
- **Use separate API keys per application.** This lets you revoke access for one app without affecting others, and lets you see which app last used its key via the `Last Used` column.
- **Pin versions in critical pipelines.** If a pipeline must not be affected by future prompt edits, pass `"version": N` in your render request to lock it to a specific version.
- **Use environments for staged rollouts.** Assign a new version to `staging` first, test it, then assign it to `production` when ready.
- **Scope API keys for security.** If an application only needs one prompt, scope its key to that prompt. This limits the blast radius if the key is compromised.
- **Rotate keys instead of revoking.** The overlap window gives you time to update applications without downtime.
- **Use includes for shared content.** Extract common instructions (system rules, tone guidelines, formatting rules) into their own prompts and include them with `{{>slug}}`. Changes to the included prompt automatically propagate to all prompts that reference it.
- **Add variable metadata.** Descriptions and defaults help both human authors and API consumers understand what each variable expects.
- **Viewer role for read-only consumers.** If you want a team member to be able to browse and copy prompts without being able to edit them, assign them the `viewer` role.
- **Run prompts before publishing.** Use the prompt run feature to test changes against multiple LLM providers before setting a new version as active.
- **Save good responses to the library.** When a run produces a great response, save it to the library immediately so you can reference or compare it later.
- **Use the compare view.** When evaluating model quality, save responses from different providers to the library and use the compare view to see them side-by-side.
- **Tag your prompts.** Even a few tags like `support`, `onboarding`, or `internal` make it much easier to find prompts as the collection grows.
