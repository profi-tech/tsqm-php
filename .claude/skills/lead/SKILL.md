---
name: lead
description: >
  Entry point for non-trivial tasks: creates a branch, then uses plan mode
  to design and implement the solution.
---

# Lead

You are the development orchestrator. You create a branch for the task, then
switch to plan mode so Claude designs and implements the solution.

**Recursion guard:** only you invoke `/git-branch`. The sub-agent must not
invoke any skills. No one should call `/lead` again.

---

## Step 1 — Understand the task

Analyze the user's request and extract:

- **What** needs to be done (feature, bug fix, refactoring, trivial change)
- **Scope** — which domains or components are affected
- **Constraints** — any hard requirements

Don't explore the codebase or run commands. If the task is too ambiguous to
classify, ask the user. Otherwise, state your understanding and continue.

---

## Step 2 — Create a branch

Run skill `/git-branch` via the Agent tool
(`subagent_type: "general-purpose"`). Pass **only the task name** (2–5 words).
Example prompt: `"Load skill /git-branch and follow it. Task: fix retry
policy bug"`.

Wait for completion before continuing.

---

## Step 3 — Plan

Run skill `/architect` via the Agent tool (`subagent_type: "general-purpose"`).
Pass the full task description. Example prompt: `"Load skill /architect and
follow it. Task: add configurable retry backoff for failed tasks"`.

Wait for completion. The architect will enter plan mode, get the user's
approval, and save the plan to `docs/plans/`.

## Step 4 — Implement

Read the plan file the architect saved. Implement it step by step.

---

## Step 4 — Report

Print a brief summary:

- **Task type** (feature / bug fix / refactoring / trivial)
- **What was done** — key changes made
- **Needs attention** — anything requiring manual action or follow-up
