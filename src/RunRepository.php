<?php
namespace Tsqm;

use DateTime;
use Exception;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\UuidHelper;

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
                INSERT INTO runs (id, created_at, scheduled_for, task, status)
                VALUES(:id, :created_at, :scheduled_for, :task, :status)
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }
            
            $createdAt = new DateTime();
            $scheduledFor = $task->getScheduledFor() ?: $createdAt;
            $res->execute([
                'id' => $runId,
                'created_at' => $createdAt,
                'scheduled_for' => $scheduledFor,
                'task' => serialize($task),
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
                throw new RepositoryError("Run not found: $runId");
            }

            return Run::fromArray($data);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get run: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateRunStatus(string $runId, string $status)
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

    public function updateRunScheduledFor(string $runId, DateTime $scheduledFor)
    {
        try {
            $res = $this->pdo->prepare("
                UPDATE runs SET scheduled_for = :scheduled_for WHERE id = :id
            ");
            if (!$res) {
                throw new Exception(PdoHelper::formatErrorInfo($this->pdo->errorInfo()));
            }

            $res->execute([
                'id' => $runId,
                'scheduled_for' => $scheduledFor->format('Y-m-d H:i:s.v'),
            ]);
        } catch (Exception $e) {
            throw new RepositoryError("Failed to update run scheduled for: " . $e->getMessage(), 0, $e);
        }
    }

    public function getScheduledRunIds(DateTime $until, int $limit): array
    {
        try {
            $res = $this->pdo->prepare("
                SELECT id FROM runs
                WHERE scheduled_for <= :until AND status != :status
                ORDER BY scheduled_for ASC
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
