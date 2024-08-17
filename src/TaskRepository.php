<?php

namespace Tsqm;

use DateTime;
use Exception;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class TaskRepository
{
    public const MYSQL_VENDOR = 'mysql';
    public const SQLITE_VENDOR = 'sqlite';

    private const MICROSECONDS_TS = 'Y-m-d H:i:s.u';

    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function createTask(PersistedTask $ptask): PersistedTask
    {
        try {
            $res = $this->pdo->prepare("
                INSERT INTO $this->table 
                    (
                        id,
                        parent_id,
                        root_id,
                        created_at,
                        scheduled_for,
                        name,
                        is_secret,
                        args,
                        retry_policy,
                        trace
                    )
                VALUES 
                    (
                        :id,
                        :parent_id,
                        :root_id,
                        :created_at,
                        :scheduled_for,
                        :name,
                        :is_secret,
                        :args,
                        :retry_policy,
                        :trace
                    )
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $row = $this->serializeRow([
                'id' => $ptask->getId(),
                'parent_id' => $ptask->getParentId(),
                'root_id' => $ptask->getRootId(),
                'created_at' => $ptask->getCreatedAt()->format(self::MICROSECONDS_TS),
                'scheduled_for' => $ptask->getScheduledFor()->format(self::MICROSECONDS_TS),
                'name' => $ptask->getName(),
                'is_secret' => (int)$ptask->getIsSecret(),
                'args' => $ptask->getArgs(),
                'retry_policy' => $ptask->getRetryPolicy(),
                'trace' => $ptask->getTrace(),
            ]);
            $res->execute($row);
            $nid = $this->pdo->lastInsertId();
            return $ptask->setNid((int)$nid);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to create task: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateTask(PersistedTask $ptask): void
    {
        try {
            if (!$ptask->getId()) {
                throw new RepositoryError("Task id is required for update");
            }

            $res = $this->pdo->prepare("
            UPDATE $this->table SET 
                scheduled_for=:scheduled_for,
                started_at=:started_at,
                finished_at=:finished_at,
                result=:result,
                error=:error,
                retried=:retried
            WHERE id = :id 
        ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $row = $this->serializeRow([
                'id' => $ptask->getId(),
                'started_at' => $ptask->getStartedAt()
                    ? $ptask->getStartedAt()->format(self::MICROSECONDS_TS)
                    : null,
                'scheduled_for' => $ptask->getScheduledFor()
                    ? $ptask->getScheduledFor()->format(self::MICROSECONDS_TS)
                    : null,
                'finished_at' => $ptask->getFinishedAt()
                    ? $ptask->getFinishedAt()->format(self::MICROSECONDS_TS)
                    : null,
                'result' => $ptask->getResult(),
                'error' => $ptask->getError(),
                'retried' => $ptask->getRetried(),
            ]);

            $res->execute($row);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to update task: " . $e->getMessage(), 0, $e);
        }
    }

    public function getTask(string $id): ?PersistedTask
    {
        try {
            $res = $this->pdo->prepare("SELECT * FROM $this->table WHERE id=:id");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute([
                'id' => UuidHelper::uuid2bin($id)
            ]);
            $row = $res->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            return $this->createTaskFromRow($row);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get task: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param int $limit
     * @param DateTime $now
     * @return array<PersistedTask>
     * @throws RepositoryError
     */
    public function getScheduledTasks(int $limit, DateTime $now): array
    {
        try {
            // MySQL strict mode fix
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === self::MYSQL_VENDOR) {
                $this->pdo->exec("SET SESSION sql_mode = ''");
            }

            // We get unfinished roots and filter out children scheduled for future
            $res = $this->pdo->prepare("
                SELECT
                    root.*, MAX(child.scheduled_for) max_scheduled_for
                FROM $this->table root
                LEFT JOIN $this->table child
                    ON child.root_id = root.root_id
                   AND child.finished_at IS NULL
                WHERE root.scheduled_for <= :now
                  AND root.finished_at IS NULL
                  AND root.parent_id IS NULL
                GROUP BY
                    root.nid
                HAVING
                    max_scheduled_for <= :now
                ORDER BY
                    root.nid
                LIMIT $limit
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'now' => $now->format(self::MICROSECONDS_TS),
            ]);

            $tasks = [];
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $tasks[] = $this->createTaskFromRow($row);
            }

            return $tasks;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get scheduled tasks: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<PersistedTask>
     */
    public function getTasksByParentId(string $parentId): array
    {
        try {
            $tasks = [];
            $res = $this->pdo->prepare("SELECT * FROM $this->table WHERE parent_id = :parent_id ORDER BY nid");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute([
                'parent_id' => UuidHelper::uuid2bin($parentId)
            ]);

            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $tasks[] = $this->createTaskFromRow($row);
            }

            return $tasks;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get tasks by parent id: " . $e->getMessage(), 0, $e);
        }
    }

    public function getLastFinishedAt(string $rootId): ?DateTime
    {
        try {
            $res = $this->pdo->prepare("
                SELECT finished_at
                FROM $this->table
                WHERE root_id = :root_id
                  AND finished_at IS NOT NULL
                ORDER BY nid DESC
                LIMIT 1
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute([
                'root_id' => UuidHelper::uuid2bin($rootId)
            ]);
            $row = $res->fetch(PDO::FETCH_ASSOC);
            if (!$row || is_null($row['finished_at'])) {
                return null;
            }
            return new DateTime($row['finished_at']);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get last finished at: " . $e->getMessage(), 0, $e);
        }
    }

    public function deleteTask(string $id): void
    {
        try {
            $res = $this->pdo->prepare("DELETE FROM $this->table WHERE root_id=:root_id");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute([
                'root_id' => UuidHelper::uuid2bin($id)
            ]);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to delete task: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createTaskFromRow(array $row): PersistedTask
    {
        $ptask = new PersistedTask();
        if (isset($row['nid'])) {
            $ptask->setNid((int)$row['nid']);
        }
        if (isset($row['id'])) {
            $id = UuidHelper::bin2uuid($row['id']);
            $ptask->setId($id);
        }
        if (isset($row['parent_id'])) {
            $parentId = UuidHelper::bin2uuid($row['parent_id']);
            $ptask->setParentId($parentId);
        }
        if (isset($row['root_id'])) {
            $rootId = UuidHelper::bin2uuid($row['root_id']);
            $ptask->setRootId($rootId);
        }
        if (isset($row['created_at'])) {
            $ptask->setCreatedAt(new DateTime($row['created_at']));
        }
        if (isset($row['scheduled_for'])) {
            $ptask->setScheduledFor(new DateTime($row['scheduled_for']));
        }
        if (isset($row['started_at'])) {
            $ptask->setStartedAt(new DateTime($row['started_at']));
        }
        if (isset($row['finished_at'])) {
            $ptask->setFinishedAt(new DateTime($row['finished_at']));
        }
        if (isset($row['name'])) {
            $ptask->setName($row['name']);
        }
        if (isset($row['is_secret'])) {
            $ptask->setIsSecret((bool)$row['is_secret']);
        }
        if (isset($row['args'])) {
            $ptask->setArgs(...SerializationHelper::unserialize($row['args']));
        }
        if (isset($row['result'])) {
            $ptask->setResult(SerializationHelper::unserialize($row['result']));
        }
        if (isset($row['error'])) {
            $error = SerializationHelper::unserializeError($row['error']);
            $ptask->setError($error);
        }
        if (isset($row['retry_policy'])) {
            $retryPolicy = SerializationHelper::unserialize($row['retry_policy']);
            $ptask->setRetryPolicy(RetryPolicy::fromArray($retryPolicy));
        }
        if (isset($row['retried'])) {
            $ptask->setRetried($row['retried']);
        }
        if (isset($row['trace'])) {
            $ptask->setTrace(SerializationHelper::unserialize($row['trace']));
        }

        return $ptask;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string|int|bool|null>
     */
    private function serializeRow(array $row): array
    {
        if (isset($row['id'])) {
            $row['id'] = UuidHelper::uuid2bin($row['id']);
        }
        if (isset($row['parent_id'])) {
            $row['parent_id'] = UuidHelper::uuid2bin($row['parent_id']);
        }
        if (isset($row['root_id'])) {
            $row['root_id'] = UuidHelper::uuid2bin($row['root_id']);
        }
        if (isset($row['args'])) {
            $row['args'] = SerializationHelper::serialize($row['args']);
        }
        if (isset($row['result'])) {
            $row['result'] = SerializationHelper::serialize($row['result']);
        }
        if (isset($row['error']) && $row['error'] instanceof Exception) {
            $row['error'] = SerializationHelper::serializeError($row['error']);
        }
        if (isset($row['retry_policy'])) {
            if (!($row['retry_policy'] instanceof RetryPolicy)) {
                throw new RepositoryError("Invalid retry policy");
            }
            $row['retry_policy'] = SerializationHelper::serialize(
                $row['retry_policy']->toArray()
            );
        }
        if (isset($row['trace'])) {
            $row['trace'] = SerializationHelper::serialize($row['trace']);
        }

        return $row;
    }
}
