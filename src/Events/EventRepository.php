<?php

namespace Tsqm\Events;

use DateTime;
use Exception;
use PDO;
use Tsqm\Errors\RepositoryError;
use Tsqm\Helpers\SerializationHelper;

class EventRepository implements EventRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addEvent(string $runId, string $type, string $taskId, $payload = null, ?string $salt = null)
    {
        try {
            $event = new Event(
                null,
                $runId,
                new DateTime(),
                $type,
                $taskId,
                $payload,
            );
            $this->pdo->prepare("
                INSERT INTO events (run_id, ts, type, task_id, payload, hash) 
                VALUES(:run_id, :ts, :type, :task_id, :payload, :hash)
            ")->execute([
                'run_id' => $event->getRunId(),
                'ts' => $event->getTs()->format('Y-m-d H:i:s.u'),
                'type' => $event->getType(),
                'task_id' => $event->getTaskId(),
                'payload' => SerializationHelper::serialize($event->getPayload()),
                'hash' => $event->getHash($salt),
            ]);
            return $event;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to add event: " . $e->getMessage(), 0, $e);
        }
    }

    public function getCompletedEvent(string $runId, string $taskId): ?Event
    {
        try {
            $st = $this->pdo->prepare("
                SELECT * FROM events 
                WHERE run_id=:run_id AND task_id=:task_id AND type in (:type1, :type2)
                ORDER BY id DESC LIMIT 1
             ");
            $st->execute([
                'run_id' => $runId,
                'task_id' => $taskId,
                'type1' => Event::TYPE_TASK_COMPLETED,
                'type2' => Event::TYPE_TASK_CRASHED,
            ]);
            $data = $st->fetch(PDO::FETCH_ASSOC);
            return $data ? Event::fromArray($data) : null;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get completed event: " . $e->getMessage(), 0, $e);
        }
    }

    public function getStartedEvents(string $runId): array
    {
        try {
            $st = $this->pdo->prepare("
                SELECT * FROM events
                WHERE run_id = :run_id and type = :type
                ORDER BY id 
            ");
            $st->execute(['run_id' => $runId, 'type' => Event::TYPE_TASK_STARTED]);
            $rows = [];
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = Event::fromArray($row);
            }
            return $rows;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get started events: " . $e->getMessage(), 0, $e);
        }
    }

    public function getFailedEvents(string $runId, string $taskId)
    {
        try {
            $st = $this->pdo->prepare("
                SELECT * FROM events
                WHERE run_id = :run_id and task_id = :task_id and type = :type
                ORDER BY id 
            ");
            $st->execute(['run_id' => $runId, 'task_id' => $taskId, 'type' => Event::TYPE_TASK_FAILED]);
            $rows = [];
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = Event::fromArray($row);
            }
            return $rows;
        } catch (Exception $e) {
            throw new RepositoryError("Failed to get failed events: " . $e->getMessage(), 0, $e);
        }
    }
}
