<?php

namespace Tsqm\Tasks;

use DateTime;
use Exception;
use PDO;
use PDOException;
use Tsqm\Errors\RepositoryError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Tasks\Task;

class TaskRepository
{
    private const MICROSECONDS_TS = 'Y-m-d H:i:s.u';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTask(Task $task): Task
    {
        try {
            $res = $this->pdo->prepare("
                INSERT INTO tsqm_tasks (trans_id, created_at, scheduled_for, name, args, retry_policy, hash)
                VALUES (:trans_id, :created_at, :scheduled_for, :name, :args, :retry_policy, :hash)
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'trans_id' => $task->getTransId(),
                'created_at' => $task->getCreatedAt()->format(self::MICROSECONDS_TS),
                'scheduled_for' => $task->getScheduledFor()->format(self::MICROSECONDS_TS),
                'name' => $task->getName(),
                'args' => $task->getArgs()
                    ? SerializationHelper::serialize($task->getArgs())
                    : null,
                'retry_policy' => $task->getRetryPolicy()
                    ? json_encode($task->getRetryPolicy())
                    : null,
                'hash' => $task->getHash(),
            ]);

            return $task->setId(
                (int)$this->pdo->lastInsertId()
            );
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
            UPDATE tsqm_tasks SET 
                parent_id=:parent_id,
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
            'parent_id' => $task->getParentId(),
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

    public function getTaskByTransId(string $transId): ?Task
    {
        $res = $this->pdo->prepare("SELECT * FROM tsqm_tasks WHERE trans_id=:trans_id ORDER BY id LIMIT 1");
        if (!$res) {
            throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
        }
        $res->execute(['trans_id' => $transId]);
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
                SELECT * FROM tsqm_tasks
                WHERE scheduled_for <= :until AND finished_at IS NULL AND parent_id = 0
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
     * @param int $parentId
     * @return array<Task>
     * @throws Exception
     * @throws PDOException
     */
    public function getTasksByParentId(int $parentId): array
    {
        $tasks = [];
        $res = $this->pdo->prepare("SELECT * FROM tsqm_tasks WHERE parent_id = :parent_id ORDER BY id");
        if (!$res) {
            throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
        }
        $res->execute(['parent_id' => $parentId]);

        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }

        return $tasks;
    }
}
