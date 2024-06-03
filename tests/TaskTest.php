<?php

namespace Tests;

use DateTime;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class TaskTest extends TestCase
{
    public function testCheckFields(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(1000))
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertEquals(0, $task->getParentId());

        $this->assertTrue(preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/', $task->getTransId()) === 1);

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getCreatedAt(), $now, 10));

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getScheduledFor(), $now, 10));

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getStartedAt(), $now, 10));

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now, 10));

        $this->assertEquals(get_class($this->simpleGreet), $task->getName());

        $this->assertEquals(['John Doe'], $task->getArgs());

        $this->assertEquals((new RetryPolicy())->setMaxRetries(3)->setMinInterval(1000), $task->getRetryPolicy());

        $this->assertEquals(0, $task->getRetried());

        $this->assertEquals(
            md5(implode('::', [
                $task->getTransId(),
                $task->getName(),
                SerializationHelper::serialize($task->getArgs()),
            ])),
            $task->getHash()
        );
    }

    public function testSameTaskRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task0 = $this->tsqm->run($task);
        $task1 = $this->tsqm->run($task0);
        $task2 = $this->tsqm->run($task0);

        $this->assertEquals($task0, $task1);
        $this->assertEquals($task0, $task2);
    }

    public function testDifferentTaskRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task0 = $this->tsqm->run($task);
        $task1 = $this->tsqm->run($task);
        $task2 = $this->tsqm->run($task);

        $this->assertNotEquals($task0, $task1);
        $this->assertNotEquals($task0, $task2);
    }
}
