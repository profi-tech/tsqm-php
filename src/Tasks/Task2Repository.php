<?php

namespace Tsqm\Runs;

use DateTime;
use Exception;
use Generator;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Errors\RunNotFound;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Tasks\Task2;

class Task2Repository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTask(Task2 $task): Task2
    {
        try {
            $res = $this->pdo->prepare("
                INSERT INTO tasks (trans_id, created_at, scheduled_for, name, args, retry_policy)
                VALUES(:trans__id, :created_at, :scheduled_for, :name, :args, :retry_policy)
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $createdAt = new DateTime();
            $scheduledFor = $task->getScheduledFor() ?: $createdAt;

            $res->execute([
                'trans_id' => $task->getTransId(),
                'created_at' => $createdAt->format('Y-m-d H:i:s.v'),
                'scheduled_for' => $scheduledFor->format('Y-m-d H:i:s.v'),
                'name' => $task->getName(),
                'args' => json_encode($task->getArgs()),
                'retry_policy' => json_encode($task->getRetryPolicy()),
            ]);

            $taskId = (int)$this->pdo->lastInsertId();
            return $this->getTask($taskId);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to create run: " . $e->getMessage(), 0, $e);
        }
    }

    public function getTask(int $taskId): Task2
    {
        try {
            $res = $this->pdo->prepare("SELECT * FROM tasks WHERE id=?");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([$taskId]);
            $data = $res->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                throw new RunNotFound("Task not found: $taskId");
            }
            return Task2::fromArray($data);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get run: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $transId
     * @return Generator<Task2>
     */
    public function getTransaction(string $transId): Generator
    {
        $res = $this->pdo->prepare("SELECT * FROM tasks WHERE trans_id=:trans_id ORDER BY id");
        if (!$res) {
            throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
        }

        $res->execute([$transId]);
        while ($data = $res->fetch(PDO::FETCH_ASSOC)) {
            yield Task2::fromArray($data);
        }
    }

    public function updateStartedAt(Task2 $task, DateTime $startedAt): Task2
    {
        try {
            $res = $this->pdo->prepare("UPDATE tasks SET started_at=? WHERE id=?");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute([$startedAt, $task->getId()]);
            return $this->getTask($task->getId());
        } catch (Exception $e) {
            throw new RepositoryError("Failed to update started_at: " . $e->getMessage(), 0, $e);
        }
    }
}
