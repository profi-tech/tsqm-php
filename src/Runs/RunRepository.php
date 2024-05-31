<?php

namespace Tsqm\Runs;

use DateTime;
use Exception;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Errors\RunNotFound;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Tasks\Task;

class RunRepository implements RunRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createRun(Task $task): Run
    {
        $runId = UuidHelper::random();

        try {
            $res = $this->pdo->prepare("
                INSERT INTO runs (id, created_at, run_at, task, status)
                VALUES(:id, :created_at, :run_at, :task, :status)
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $createdAt = new DateTime();
            $runAt = $task->getScheduledFor() ?: $createdAt;
            $res->execute([
                'id' => $runId,
                'created_at' => $createdAt->format('Y-m-d H:i:s.v'),
                'run_at' => $runAt->format('Y-m-d H:i:s.v'),
                'task' => SerializationHelper::serialize($task),
                'status' => Run::STATUS_CREATED,
            ]);

            return $this->getRun($runId);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to create run: " . $e->getMessage(), 0, $e);
        }
    }

    public function getRun(string $runId): Run
    {
        try {
            $res = $this->pdo->prepare("SELECT * FROM runs WHERE id=?");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([$runId]);
            $data = $res->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                throw new RunNotFound("Run not found: $runId");
            }

            return Run::fromArray($data);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get run: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateRunStatus(string $runId, string $status): void
    {
        try {
            $res = $this->pdo->prepare("
                UPDATE runs SET status = :status WHERE id = :id
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'id' => $runId,
                'status' => $status,
            ]);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to update run status: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateRunAt(string $runId, DateTime $runAt): Run
    {
        try {
            $res = $this->pdo->prepare("
                UPDATE runs SET run_at = :run_at WHERE id = :id
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            $res->execute([
                'id' => $runId,
                'run_at' => $runAt->format('Y-m-d H:i:s.v'),
            ]);

            return $this->getRun($runId);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to update run scheduled for: " . $e->getMessage(), 0, $e);
        }
    }

    public function getNextRunIds(DateTime $until, int $limit): array
    {
        try {
            $res = $this->pdo->prepare("
                SELECT id FROM runs
                WHERE run_at <= :until AND status != :status
                ORDER BY run_at ASC
                LIMIT $limit
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'until' => $until->format('Y-m-d H:i:s.v'),
                'status' => Run::STATUS_FINISHED,
            ]);

            $runIds = [];
            while ($runId = $res->fetch(PDO::FETCH_COLUMN)) {
                $runIds[] = $runId;
            }

            return $runIds;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get scheduled run ids: " . $e->getMessage(), 0, $e);
        }
    }
}
