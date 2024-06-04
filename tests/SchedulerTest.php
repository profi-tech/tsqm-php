<?php

namespace Tests;

use DateTime;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\RetryPolicy;

class SchedulerTest extends TestCase
{
    public function testDefaultScheduledFor(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);
        $this->assertDateEquals($task->getScheduledFor(), new DateTime(), 50);
        $this->assertTrue($task->isFinished());
    }

    public function testForceAsync(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task, true);
        $this->assertDateEquals($task->getScheduledFor(), new DateTime(), 50);
        $this->assertFalse($task->isFinished());
    }

    public function testScheduledFor(): void
    {
        $scheduleFor = (new DateTime())->modify('+1 day');

        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduleFor);

        $task = $this->tsqm->runTask($task);

        $this->assertDateEquals($scheduleFor, $task->getScheduledFor());
        $this->assertFalse($task->isFinished());
    }

    public function testRetryScheduleFor(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(3)
                    ->setMinInterval(1500)
            );

        $task = $this->tsqm->runTask($task);

        $this->assertDateEquals(
            $task->getScheduledFor(),
            (new DateTime())->modify('+1500 milliseconds')
        );
        $this->assertFalse($task->isFinished());
    }

    public function testRunScheduledRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');
        $task = $this->tsqm->runTask($task);
        $this->assertTrue($task->isFinished());

        $task = $this->tsqm->getTask($task->getRootId());
        $this->assertTrue($task->isFinished());
    }

    public function testListScheduledTasks(): void
    {
        $scheduledFor = (new DateTime())->modify('+10 second');
        $task = (new Task())
            ->setCallable($this->simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduledFor);

        $task1 = $this->tsqm->runTask($task);
        $task2 = $this->tsqm->runTask($task);
        $task3 = $this->tsqm->runTask($task);

        $scheduledTasks = $this->tsqm->getScheduledTasks($scheduledFor, 10);
        $this->assertCount(3, $scheduledTasks);
        $this->assertEquals([$task1, $task2, $task3], $scheduledTasks);
    }

    public function testListScheduledTasksUntil(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1)->setMinInterval(0))
            ->setArgs('John Doe');

        $this->tsqm->runTask($task);
        $this->tsqm->runTask($task);
        $this->tsqm->runTask($task);

        $scheduledTasks = $this->tsqm->getScheduledTasks((new DateTime())->modify("-10 second"), 10);
        $this->assertCount(0, $scheduledTasks);
    }

    public function testListScheduledTasksLimit(): void
    {
        $scheduledFor = (new DateTime())->modify('+10 second');
        $task = (new Task())
            ->setCallable($this->simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduledFor);

        $task1 = $this->tsqm->runTask($task);
        $task2 = $this->tsqm->runTask($task);

        $scheduledTasks = $this->tsqm->getScheduledTasks($scheduledFor, 2);
        $this->assertCount(2, $scheduledTasks);
        $this->assertEquals([$task1, $task2], $scheduledTasks);
    }
}
