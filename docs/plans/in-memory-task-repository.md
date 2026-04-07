# Plan: In-Memory Task Repository (#83)

## Context

`TaskRepository` is a concrete PDO-based class instantiated directly inside `Tsqm`. Every test requires a running database. The goal is to extract an interface and provide an in-memory implementation so tests can run without DB infrastructure.

**This is a breaking change** — `Tsqm` constructor no longer accepts PDO directly. Repository is provided via `Options`.

## Approach

### 1. Create `Tsqm\Repository` namespace (`src/Repository/`)

All repository classes live under `Tsqm\Repository\`, following the pattern of `Tsqm\Container\`, `Tsqm\Queue\`, etc.

### 2. Create `TaskRepositoryInterface` (`src/Repository/TaskRepositoryInterface.php`)

Extract public methods from the current `TaskRepository`.

### 3. Move `TaskRepository` → `src/Repository/TaskRepository.php`

- Namespace becomes `Tsqm\Repository`
- Add `implements TaskRepositoryInterface`
- Delete old `src/TaskRepository.php` (no backward-compat shim)

### 4. Create `InMemoryTaskRepository` (`src/Repository/InMemoryTaskRepository.php`)

Stores tasks in `array<string, PersistedTask>` keyed by UUID. Replicates the filtering logic of `getScheduledTasks` in pure PHP. Uses an auto-incrementing counter for `nid`.

### 5. Change `Tsqm` constructor — remove PDO, get repository from Options

- Remove `PDO $pdo` parameter from `Tsqm::__construct`
- Constructor becomes `__construct(?Options $options = null)`
- Repository is optional in Options via `setRepository(TaskRepositoryInterface)` (defaults to `InMemoryTaskRepository`)
- `private TaskRepositoryInterface $repository`

### 6. Update `Options`

- Add `setRepository(TaskRepositoryInterface)` / `getRepository(): TaskRepositoryInterface`
- Default: `InMemoryTaskRepository` — consistent with logger/queue which also have null-object defaults. Keeps `new Tsqm()` working without configuration.

### 7. Update tests and examples

- All test setUp: create repository, pass via Options
- `TestCase::getLastTaskByParentId()` — use repository instead of raw PDO
- Add `InMemoryTaskRepositoryTest` for the new implementation
- Update any examples that construct `Tsqm` with PDO

## Files to modify

| File                                         | Change                                                    |
| -------------------------------------------- | --------------------------------------------------------- |
| `src/Repository/TaskRepositoryInterface.php` | **New** — interface                                       |
| `src/Repository/TaskRepository.php`          | **Moved** from `src/TaskRepository.php`, add `implements` |
| `src/Repository/InMemoryTaskRepository.php`  | **New** — in-memory implementation                        |
| `src/TaskRepository.php`                     | **Delete**                                                |
| `src/Options.php`                            | Add `setRepository` / `getRepository`                     |
| `src/Tsqm.php`                               | Remove PDO param, use interface from Options              |
| `tests/TestCase.php`                         | Update setUp and `getLastTaskByParentId()`                |
| `tests/InMemoryTaskRepositoryTest.php`       | **New** — tests for in-memory repo                        |
| `tests/SerializationTest.php`                | Update repository construction                            |
| `examples/`                                  | Update any Tsqm construction                              |

## Verification

1. `make check` — all checks pass (lint, analyse, tests)
2. New `InMemoryTaskRepositoryTest` passes
