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

        $task = $this->tsqm->run($task);
        $this->assertTrue(
            $this->assertHelper->assertDateEquals($task->getScheduledFor(), new DateTime(), 50)
        );
    }

    public function testScheduledFor(): void
    {
        $scheduleFor = (new DateTime())->modify('+1 day');

        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduleFor);

        $task = $this->tsqm->run($task);

        $this->assertEquals(
            $scheduleFor->format('Y-m-d H:i:s.v'),
            $task->getScheduledFor()->format('Y-m-d H:i:s.v')
        );
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

        $task = $this->tsqm->run($task);

        $this->assertTrue(
            $this->assertHelper->assertDateEquals(
                $task->getScheduledFor(),
                (new DateTime())->modify('+1500 milliseconds')
            )
        );
    }

    public function testRunScheduledRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');
        $task = $this->tsqm->run($task);
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

        $task1 = $this->tsqm->run($task);
        $task2 = $this->tsqm->run($task);
        $task3 = $this->tsqm->run($task);

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

        $this->tsqm->run($task);
        $this->tsqm->run($task);
        $this->tsqm->run($task);

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

        $task1 = $this->tsqm->run($task);
        $task2 = $this->tsqm->run($task);

        $scheduledTasks = $this->tsqm->getScheduledTasks($scheduledFor, 2);
        $this->assertCount(2, $scheduledTasks);
        $this->assertEquals([$task1, $task2], $scheduledTasks);
    }
}
