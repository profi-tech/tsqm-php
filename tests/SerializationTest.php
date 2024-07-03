<?php

namespace Tests;

use DateTime;
use PDOException;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Task;
use Tsqm\TaskRepository;

class SerializationTest extends TestCase
{
    /**
     * Test checks fix for the https://github.com/php/php-src/issues/9529
     */
    public function testPdoExceptionBug(): void
    {
        $previousErrorReporting = error_reporting();

        error_reporting(E_ALL & ~E_NOTICE);

        $taskId = UuidHelper::random();
        $repository = new TaskRepository($this->pdo, "tsqm_tasks");
        $task = (new Task())
            ->setId($taskId)
            ->setRootId($taskId)
            ->setCreatedAt(new DateTime())
            ->setScheduledFor(new DateTime())
            ->setName("Random task name");
        $task = $repository->createTask($task);

        $checks = [
            "00S02" => 0,
            "42S02" => 42,
            "12345678" => 12345678,
            123456789 => 123456789,
        ];

        foreach ($checks as $code => $expected) {
            $task->setError(new PDOException("Random PDO error", $code));
            $repository->updateTask($task);
            $this->assertEquals($expected, $task->getError()->getCode(), "Failed with code '$code'");
            $task = $repository->getTask($taskId);
            $this->assertEquals($expected, $task->getError()->getCode(), "Failed with code '$code'");
        }

        error_reporting($previousErrorReporting);
    }
}
