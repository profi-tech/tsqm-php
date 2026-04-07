---
name: git-branch
description: >
  Creates a feature branch from fresh origin/main. Called by lead before
  starting work — not intended for direct user invocation.
---

# Git Branch

Create a git branch for the task and switch to it.

**IMPORTANT:** This is the only job of this skill. Do NOT do anything beyond
the steps described below. Don't read project files, explore the codebase,
run tests, or load other skills. Only git commands.

---

## Step 1 — Check working directory is clean

Run `git status --porcelain`. If the output is non-empty (there are
uncommitted or untracked changes) — **stop**. Tell the user which files are
affected and take no further action.

---

## Step 2 — Generate branch name

Based on the task description, generate a branch name:

- 2–4 words joined by hyphens, lowercase, only Latin letters and digits

Examples: `fix-retry-policy-bug`, `add-wait-interval`, `migrate-to-carbon`.

---

## Step 3 — Create the branch

Run sequentially:

1. `git fetch origin main` — get fresh main
2. `git checkout -b <branch-name> --no-track origin/main` — create and switch

If `git fetch` fails — report the error and stop.

If a branch with this name already exists — add a numeric suffix
(`add-index-command-2`).

---

## Step 4 — Confirm

Print one line with the result:

```
Branch `<branch-name>` created from origin/main.
```
