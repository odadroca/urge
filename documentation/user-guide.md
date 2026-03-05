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
| **admin** | Everything: create/edit/delete prompts, manage users and roles, manage all API keys |
| **editor** | Create and edit prompts, create versions, set active versions, manage their own API keys |
| **viewer** | Read-only access to all prompts and versions, manage their own API keys |

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

### Deleting a prompt

Only admins can delete prompts. The delete button appears at the bottom of the prompt detail page. Deleting a prompt permanently removes it and all its versions.

---

## Version Control

Every change to prompt content is saved as a new, immutable version. Versions are numbered sequentially per prompt (v1, v2, v3…). Old versions are never modified or deleted.

### Creating a new version

1. Open a prompt and click **New Version**.
2. Write or paste the prompt content. Use `{{variable_name}}` for any values you want to fill in at call time.
3. Add an optional commit message describing what changed.
4. Click **Save Version**.

Variables are extracted automatically — you will see them listed as badges.

The new version is **not active** by default. It must be promoted explicitly.

### Setting the active version

The active version is what the API returns when no version number is specified. To change it:

1. Open **History** from the prompt list or detail page.
2. Find the version you want to promote.
3. Click **Set Active**.

You can also set a version as active from the version detail page.

### Browsing history

Click **History** on any prompt to see all versions in reverse chronological order, including who created each one, when, and what the commit message was. Click **View** to read the full content of any past version.

---

## API Keys

API keys allow external applications to query URGE programmatically. Each key is tied to a user and inherits that user's role.

### Creating an API key

1. Click **API Keys** in the navigation bar.
2. Click **New API Key**.
3. Give it a descriptive name (e.g. "Production App", "Dev Laptop").
4. Optionally set an expiry date.
5. Click **Generate Key**.

**The full key is shown exactly once** on the next screen. Copy it immediately — it cannot be retrieved again from the UI. If you lose it, revoke it and generate a new one.

The key list shows a short preview (first 8 characters) and the last time the key was used, so you can identify and audit your keys.

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

## Tips

- **Use slugs as stable identifiers.** Because slugs never change, you can hard-code them in your application config and rename prompts freely in the UI without breaking anything.
- **Use commit messages.** Even brief notes like "Added product name variable" or "Adjusted tone" make version history much easier to navigate later.
- **Use separate API keys per application.** This lets you revoke access for one app without affecting others, and lets you see which app last used its key via the `Last Used` column.
- **Pin versions in critical pipelines.** If a pipeline must not be affected by future prompt edits, pass `"version": N` in your render request to lock it to a specific version.
- **Viewer role for read-only consumers.** If you want a team member to be able to browse and copy prompts without being able to edit them, assign them the `viewer` role.
