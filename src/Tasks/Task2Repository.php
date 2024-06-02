<?php

namespace Tsqm\Tasks;

use DateTime;
use Exception;
use Generator;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Tasks\Task2;

class Task2Repository
{
    private const MILISECONDS_TS = 'Y-m-d H:i:s.v';
    private const MICROSECONDS_TS = 'Y-m-d H:i:s.u';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTask(Task2 $task): Task2
    {
        try {
            $res = $this->pdo->prepare("
                INSERT INTO tasks (trans_id, created_at, scheduled_for, name, args, retry_policy, hash)
                VALUES (:trans_id, :created_at, :scheduled_for, :name, :args, :retry_policy, :hash)
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'trans_id' => $task->getTransId(),
                'created_at' => $task->getCreatedAt()->format(self::MICROSECONDS_TS),
                'scheduled_for' => $task->getScheduledFor()->format(self::MILISECONDS_TS),
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

    public function updateTask(Task2 $task): void
    {
        if (!$task->getId()) {
            throw new RepositoryError("Task id is required for update");
        }

        $res = $this->pdo->prepare("
            UPDATE tasks SET 
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
                ? $task->getScheduledFor()->format(self::MILISECONDS_TS)
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
}
