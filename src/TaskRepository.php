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
            $res->execute([
                'id' => $task->getId(),
                'parent_id' => $task->getParentId(),
                'root_id' => $task->getRootId(),
                'created_at' => $task->getCreatedAt()->format(self::MICROSECONDS_TS),
                'scheduled_for' => $task->getScheduledFor()->format(self::MICROSECONDS_TS),
                'name' => $task->getName(),
                'is_secret' => (int)$task->getIsSecret(),
                'args' => $task->getArgs()
                    ? SerializationHelper::serialize($task->getArgs())
                    : null,
                'retry_policy' => $task->getRetryPolicy()
                    ? json_encode($task->getRetryPolicy())
                    : null,
            ]);
            return $task;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to create run: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateTask(Task $task): void
    {
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

        $res->execute([
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
            'result' => !is_null($task->getResult())
                ? SerializationHelper::serialize($task->getResult())
                : null,
            'error' => $task->getError()
                ? SerializationHelper::serialize($task->getError())
                : null,
            'retried' => $task->getRetried(),
        ]);
    }

    public function getTask(string $id): ?Task
    {
        $res = $this->pdo->prepare("SELECT * FROM $this->table WHERE id=:id");
        if (!$res) {
            throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
        }
        $res->execute(['id' => $id]);
        $row = $res->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return Task::fromArray($row);
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
                $tasks[] = Task::fromArray($row);
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
        $tasks = [];
        $res = $this->pdo->prepare("SELECT * FROM $this->table WHERE parent_id = :parent_id ORDER BY nid");
        if (!$res) {
            throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
        }
        $res->execute(['parent_id' => $parentId]);

        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }

        return $tasks;
    }

    public function deleteTask(string $id): void
    {
        $res = $this->pdo->prepare("DELETE FROM $this->table WHERE root_id=:root_id");
        if (!$res) {
            throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
        }
        $res->execute(['root_id' => $id]);
    }
}
