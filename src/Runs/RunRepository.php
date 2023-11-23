<?php

namespace Tsqm\Runs;

use DateTime;
use Exception;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Tasks\Task;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class RunRepository implements RunRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createRun(Task $task, DateTime $createdAt, DateTime $scheduledFor): Run
    {
        try {
            $runId = UuidHelper::random();
            $run = new Run(
                $runId,
                $createdAt,
                $scheduledFor,
                $task,
                Run::STATUS_CREATED
            );

            $this->pdo->prepare("
                INSERT INTO runs (id, created_at, scheduled_for, task, status)
                VALUES(:id, :created_at, :scheduled_for, :task, :status)
            ")->execute([
                'id' => $run->getId(),
                'created_at' => $run->getCreatedAt()->format('Y-m-d H:i:s.u'),
                'scheduled_for' => $run->getScheduledFor()->format('Y-m-d H:i:s.v'),
                'task' => SerializationHelper::serialize($run->getTask()),
                'status' => $run->getStatus(),
            ]);

            return $run;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to create run: " . $e->getMessage(), 0, $e);
        }
    }

    public function getRun(string $runId): ?Run
    {
        try {
            $st = $this->pdo->prepare("SELECT * FROM runs WHERE id=?");
            $st->execute([$runId]);
            $data = $st->fetch(PDO::FETCH_ASSOC);
            return $data ? Run::fromArray($data) : null;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get run: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateRunStatus(string $runId, string $status)
    {
        try {
            $this->pdo->prepare("
                UPDATE runs SET status = :status WHERE id = :id
            ")->execute([
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
            $this->pdo->prepare("
                UPDATE runs SET scheduled_for = :scheduled_for WHERE id = :id
            ")->execute([
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
            $st = $this->pdo->prepare("
                SELECT id FROM runs
                WHERE scheduled_for <= :until AND status != :status
                ORDER BY scheduled_for ASC
                LIMIT $limit
            ");
            $st->execute([
                'until' => $until->format('Y-m-d H:i:s.v'),
                'status' => Run::STATUS_FINISHED,
            ]);
            $runIds = [];
            while ($runId = $st->fetch(PDO::FETCH_COLUMN)) {
                $runIds[] = $runId;
            }
            return $runIds;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get scheduled run ids: " . $e->getMessage(), 0, $e);
        }
    }
}
