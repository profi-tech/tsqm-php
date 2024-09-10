<?php

namespace Tsqm;

use DateTime;

interface TsqmInterface
{
    /**
     * Runs a task and returns the persisted task.
     * @param Task $task
     * @param bool $async
     * @return PersistedTask
     */
    public function run(Task $task, bool $async = false): PersistedTask;

    /**
     * Gets a persisted task by id.
     * @param string $id
     * @return PersistedTask|null
     */
    public function get(string $id): ?PersistedTask;

    /**
     * Lists scheduled tasks.
     * @param int $limit
     * @param DateTime|null $now
     * @return array<PersistedTask>
     */
    public function list(int $limit = 100, ?DateTime $now = null): array;

    /**
     * Runs scheduled tasks in a polling mode.
     * @param int $limit
     * @param int $delay — time in seconds to "step back" from the current time (usefull for the fallback mode)
     * @param int $emptySleep
     */
    public function poll(int $limit = 100, int $delay = 0, int $emptySleep = 10): void;

    /**
     * Listens for the queue and runs scheduled tasks.
     * @param string $taskName
     */
    public function listen(string $taskName): void;

    /**
     * @deprecated
     * @see TsqmInterface::run
     */
    public function runTask(Task $task, bool $async = false): PersistedTask;

    /**
     * @deprecated
     * @see TsqmInterface::get
     */
    public function getTask(string $id): ?PersistedTask;

    /**
     * @deprecated
     * @see TsqmInterface::list
     * @return array<PersistedTask>
     */
    public function getScheduledTasks(int $limit = 100, ?DateTime $now = null): array;

    /**
     * @deprecated
     * @see TsqmInterface::poll
     */
    public function pollScheduledTasks(int $limit = 100, int $delay = 0, int $emptySleep = 10): void;

    /**
     * @deprecated
     * @see TsqmInterface::listen
     */
    public function listenQueuedTasks(string $taskName): void;
}
