<?php

namespace Tests;

use DateTime;
use Examples\TsqmContainer;
use Tsqm\Options;
use Tsqm\Task;
use Tsqm\RetryPolicy;
use Tsqm\Tsqm;

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

    public function testForceSyncForceAsync(): void
    {

        $tsqm = new Tsqm(
            $this->pdo,
            (new Options())
                ->setContainer(new TsqmContainer($this->psrContainer))
                ->setForceSync(true)
        );

        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task = $tsqm->runTask($task, true);
        $this->assertDateEquals($task->getStartedAt(), new DateTime(), 50);
        $this->assertNotNull($task->getResult());
        $this->assertTrue($task->isFinished());
    }

    public function testForceSyncScheduledFor(): void
    {

        $tsqm = new Tsqm(
            $this->pdo,
            (new Options())
                ->setContainer(new TsqmContainer($this->psrContainer))
                ->setForceSync(true)
        );

        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify('+1 day'));

        $task = $tsqm->runTask($task);
        $this->assertDateEquals($task->getStartedAt(), new DateTime(), 50);
        $this->assertNotNull($task->getResult());
        $this->assertTrue($task->isFinished());
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

        $scheduledTasks = $this->tsqm->getScheduledTasks(10, $scheduledFor);
        $this->assertCount(3, $scheduledTasks);
        $this->assertEquals(
            [$task1->getId(), $task2->getId(), $task3->getId()],
            array_map(fn(Task $task) => $task->getId(), $scheduledTasks)
        );
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

        $scheduledTasks = $this->tsqm->getScheduledTasks(10, (new DateTime())->modify("-10 second"));
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

        $scheduledTasks = $this->tsqm->getScheduledTasks(2, $scheduledFor);
        $this->assertCount(2, $scheduledTasks);
        $this->assertEquals(
            [$task1->getId(), $task2->getId()],
            array_map(fn(Task $task) => $task->getId(), $scheduledTasks)
        );
    }

    public function testListScheduledTasksWithScheduledChildren(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithPurchaseFailAndRetryInterval)
            ->setArgs('John Doe');

        $this->tsqm->runTask($task);
        $tasks = $this->tsqm->getScheduledTasks();
        // Tasks must be empty becasue inner failed purchase was scheduled for the future interval
        $this->assertCount(0, $tasks);
    }
}
