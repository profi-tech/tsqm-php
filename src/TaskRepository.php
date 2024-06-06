<?php

namespace Tsqm;

use DateTime;
use Exception;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Task;

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

    public function createTask(Task $task): Task
    {
        try {
            $res = $this->pdo->prepare("
                INSERT INTO $this->table 
                    (id, parent_id, root_id, created_at, scheduled_for, name, is_secret, args, retry_policy)
                VALUES 
                    (:id, :parent_id, :root_id, :created_at, :scheduled_for, :name, :is_secret, :args, :retry_policy)
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $row = $this->serializeRow([
                'id' => $task->getId(),
                'parent_id' => $task->getParentId(),
                'root_id' => $task->getRootId(),
                'created_at' => $task->getCreatedAt()->format(self::MICROSECONDS_TS),
                'scheduled_for' => $task->getScheduledFor()->format(self::MICROSECONDS_TS),
                'name' => $task->getName(),
                'is_secret' => (int)$task->getIsSecret(),
                'args' => $task->getArgs(),
                'retry_policy' => $task->getRetryPolicy(),
            ]);
            $res->execute($row);
            return $task;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to create task: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateTask(Task $task): void
    {
        try {
            if (!$task->getId()) {
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
                'id' => $task->getId(),
                'started_at' => $task->getStartedAt()
                    ? $task->getStartedAt()->format(self::MICROSECONDS_TS)
                    : null,
                'scheduled_for' => $task->getScheduledFor()
                    ? $task->getScheduledFor()->format(self::MICROSECONDS_TS)
                    : null,
                'finished_at' => $task->getFinishedAt()
                    ? $task->getFinishedAt()->format(self::MICROSECONDS_TS)
                    : null,
                'result' => $task->getResult(),
                'error' => $task->getError(),
                'retried' => $task->getRetried(),
            ]);

            $res->execute($row);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to update task: " . $e->getMessage(), 0, $e);
        }
    }

    public function getTask(string $id): ?Task
    {
        try {
            $res = $this->pdo->prepare("SELECT * FROM $this->table WHERE id=:id");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute(['id' => $id]);
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
     * @param DateTime $until
     * @param int $limit
     * @return array<Task>
     * @throws RepositoryError
     */
    public function getScheduledTasks(DateTime $until, int $limit): array
    {
        try {
            $res = $this->pdo->prepare("
                SELECT * FROM $this->table
                WHERE scheduled_for <= :until AND finished_at IS NULL AND parent_id IS NULL
                ORDER BY scheduled_for ASC
                LIMIT $limit
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'until' => $until->format(self::MICROSECONDS_TS),
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
     * @return array<Task>
     */
    public function getTasksByParentId(string $parentId): array
    {
        try {
            $tasks = [];
            $res = $this->pdo->prepare("SELECT * FROM $this->table WHERE parent_id = :parent_id ORDER BY nid");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute(['parent_id' => $parentId]);

            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $tasks[] = $this->createTaskFromRow($row);
            }

            return $tasks;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get tasks by parent id: " . $e->getMessage(), 0, $e);
        }
    }

    public function deleteTask(string $id): void
    {
        try {
            $res = $this->pdo->prepare("DELETE FROM $this->table WHERE root_id=:root_id");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute(['root_id' => $id]);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to delete task: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createTaskFromRow(array $row): Task
    {
        $task = new Task();
        if (isset($row['id'])) {
            $task->setId($row['id']);
        }
        if (isset($row['parent_id'])) {
            $task->setParentId($row['parent_id']);
        }
        if (isset($row['root_id'])) {
            $task->setRootId($row['root_id']);
        }
        if (isset($row['created_at'])) {
            $task->setCreatedAt(new DateTime($row['created_at']));
        }
        if (isset($row['scheduled_for'])) {
            $task->setScheduledFor(new DateTime($row['scheduled_for']));
        }
        if (isset($row['started_at'])) {
            $task->setStartedAt(new DateTime($row['started_at']));
        }
        if (isset($row['finished_at'])) {
            $task->setFinishedAt(new DateTime($row['finished_at']));
        }
        if (isset($row['name'])) {
            $task->setName($row['name']);
        }
        if (isset($row['is_secret'])) {
            $task->setIsSecret((bool)$row['is_secret']);
        }
        if (isset($row['args'])) {
            $task->setArgs(...SerializationHelper::unserialize($row['args']));
        }
        if (isset($row['result'])) {
            $task->setResult(SerializationHelper::unserialize($row['result']));
        }
        if (isset($row['error'])) {
            $error = json_decode($row['error'], true);
            $task->setError(
                new $error['class']($error['message'], $error['code'])
            );
        }
        if (isset($row['retry_policy'])) {
            $data = json_decode($row['retry_policy'], true);
            $retryPolicy = (new RetryPolicy())
                ->setMaxRetries($data['maxRetries'])
                ->setMinInterval($data['minInterval']);
            $task->setRetryPolicy($retryPolicy);
        }
        if (isset($row['retried'])) {
            $task->setRetried($row['retried']);
        }

        return $task;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string|int|bool|null>
     */
    private function serializeRow(array $row): array
    {
        if (isset($row['args'])) {
            $row['args'] = SerializationHelper::serialize($row['args']);
        }
        if (isset($row['result'])) {
            $row['result'] = SerializationHelper::serialize($row['result']);
        }
        if (isset($row['error'])) {
            $row['error'] = json_encode([
                'class' => get_class($row['error']),
                'message' => $row['error']->getMessage(),
                'code' => $row['error']->getCode(),
            ]);
        }
        if (isset($row['retry_policy'])) {
            $row['retry_policy'] = json_encode($row['retry_policy']);
        }
        return $row;
    }
}
