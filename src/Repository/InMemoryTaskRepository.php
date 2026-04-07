<?php

namespace Tsqm\Repository;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Tsqm\Errors\RepositoryError;
use Tsqm\PersistedTask;

/**
 * Non-persistent task repository that stores everything in a PHP array.
 * Intended for unit/integration tests so they can run without a database.
 */
class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var array<string, PersistedTask> */
    private array $tasks = [];

    /** @var array<string, int> nid by task id */
    private array $nids = [];

    private int $nextNid = 1;

    public function createTask(PersistedTask $ptask): PersistedTask
    {
        $id = $ptask->getId();
        if (isset($this->tasks[$id])) {
            throw new RepositoryError("Failed to create task: Integrity constraint violation: task $id already exists");
        }
        $nid = $this->nextNid++;
        $ptask->setNid($nid);
        $this->tasks[$id] = clone $ptask;
        $this->nids[$id] = $nid;
        return $ptask;
    }

    public function updateTask(PersistedTask $ptask): void
    {
        $id = $ptask->getId();
        if (!$id) {
            throw new RepositoryError("Task id is required for update");
        }
        if (isset($this->tasks[$id])) {
            $this->tasks[$id] = clone $ptask;
        }
    }

    public function getTask(string $id): ?PersistedTask
    {
        if (!isset($this->tasks[$id])) {
            return null;
        }
        return clone $this->tasks[$id];
    }

    public function isTaskExists(string $id): bool
    {
        return isset($this->tasks[$id]);
    }

    /**
     * @param int $limit
     * @param DateTimeInterface $now
     * @return array<PersistedTask>
     */
    public function getScheduledTasks(int $limit, DateTimeInterface $now): array
    {
        $nowTs = $now->format('Y-m-d H:i:s.u');

        // Collect root tasks (no parent) that are unfinished and scheduled <= now
        $roots = [];
        foreach ($this->tasks as $task) {
            if ($task->getParentId() !== null) {
                continue;
            }
            if ($task->isFinished()) {
                continue;
            }
            if ($task->getScheduledFor()->format('Y-m-d H:i:s.u') > $nowTs) {
                continue;
            }
            $roots[$task->getId()] = $task;
        }

        // Filter out roots that have unfinished children scheduled in the future
        foreach ($this->tasks as $task) {
            if ($task->isFinished()) {
                continue;
            }
            if (!$task->hasRoot()) {
                continue;
            }
            $rootId = $task->getRootId();
            if (!isset($roots[$rootId])) {
                continue;
            }
            if ($task->getScheduledFor()->format('Y-m-d H:i:s.u') > $nowTs) {
                unset($roots[$rootId]);
            }
        }

        // Sort by nid
        usort($roots, function (PersistedTask $a, PersistedTask $b): int {
            return ($this->nids[$a->getId()] ?? 0) <=> ($this->nids[$b->getId()] ?? 0);
        });

        $result = array_slice($roots, 0, $limit);
        return array_map(fn(PersistedTask $t) => clone $t, $result);
    }

    /**
     * @return array<PersistedTask>
     */
    public function getTasksByParentId(string $parentId): array
    {
        $tasks = [];
        foreach ($this->tasks as $task) {
            if ($task->getParentId() === $parentId) {
                $tasks[] = clone $task;
            }
        }

        usort($tasks, function (PersistedTask $a, PersistedTask $b): int {
            return ($this->nids[$a->getId()] ?? 0) <=> ($this->nids[$b->getId()] ?? 0);
        });

        return $tasks;
    }

    public function getLastFinishedAt(string $rootId): ?DateTimeInterface
    {
        $lastFinishedAt = null;
        $lastNid = -1;

        foreach ($this->tasks as $task) {
            if (!$task->hasRoot() || $task->getRootId() !== $rootId) {
                continue;
            }
            if (!$task->isFinished()) {
                continue;
            }
            $nid = $this->nids[$task->getId()] ?? 0;
            if ($nid > $lastNid) {
                $lastNid = $nid;
                $lastFinishedAt = $task->getFinishedAt();
            }
        }

        return $lastFinishedAt ? CarbonImmutable::instance($lastFinishedAt) : null;
    }

    public function deleteTaskTree(string $rootId): void
    {
        foreach ($this->tasks as $id => $task) {
            if ($task->hasRoot() && $task->getRootId() === $rootId) {
                unset($this->tasks[$id]);
                unset($this->nids[$id]);
            }
        }
    }
}
