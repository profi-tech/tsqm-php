<?php

namespace Tsqm\Repository;

use DateTimeInterface;
use Tsqm\PersistedTask;

interface TaskRepositoryInterface
{
    public function createTask(PersistedTask $ptask): PersistedTask;

    public function updateTask(PersistedTask $ptask): void;

    public function getTask(string $id): ?PersistedTask;

    public function isTaskExists(string $id): bool;

    /**
     * @param int $limit
     * @param DateTimeInterface $now
     * @return array<PersistedTask>
     */
    public function getScheduledTasks(int $limit, DateTimeInterface $now): array;

    /**
     * @return array<PersistedTask>
     */
    public function getTasksByParentId(string $parentId): array;

    public function getLastFinishedAt(string $rootId): ?DateTimeInterface;

    public function deleteTaskTree(string $rootId): void;
}
