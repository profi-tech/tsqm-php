<?php

namespace Tests;

use DateTime;
use Examples\Greeter\GreeterError;
use Examples\Greeter\Greeting;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class TaskFlowTest extends TestCase
{
    public function testTaskSuccess(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $task->getResult());
        $this->assertNull($task->getError());
    }

    public function testTaskCleanup(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);
        $this->assertTrue($task->isFinished());
        $this->assertNull($task->getError());

        $task = $this->tsqm->getTask($task->getId());
        $this->assertNull($task);
    }


    public function testTaskSuccessRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $now = new DateTime();

        $task = $this->tsqm->runTask($task);

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $task->getResult());
        $this->assertNull($task->getError());
        $this->assertEquals(0, $task->getRetried());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->runTask($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);
            $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $task->getResult());
            $this->assertNull($task->getError());
            $this->assertEquals(0, $task->getRetried());
        }
    }

    public function testTaskScheduled(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify("+10 second"));

        $task = $this->tsqm->runTask($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());
    }

    public function testTaskScheduledRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify("+10 second"));

        $task = $this->tsqm->runTask($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->getTask($task->getRootId());
            $task = $this->tsqm->runTask($task);
            $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
            $this->assertNull($task->getFinishedAt());
            $this->assertNull($task->getResult());
            $this->assertNull($task->getError());
        }
    }

    public function testFailTaskScheduled(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify("+10 second"));

        $task = $this->tsqm->runTask($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());
    }


    public function testTaskFailed(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($this->getResult());
    }

    public function testTaskFailedRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($this->getResult());
        $this->assertEquals(0, $task->getRetried());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->getTask($task->getRootId());
            $task = $this->tsqm->runTask($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);
            $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
            $this->assertNull($this->getResult());
            $this->assertEquals(0, $task->getRetried());
        }
    }

    public function testTaskFailedAndScheduled(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(1)
                    ->setMinInterval(10000)
            );

        $task = $this->tsqm->runTask($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
    }

    public function testTaskFailedAndSuccesfullyRetried(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(3)
                    ->setMinInterval(0)
            );

        // Initial failed run
        $task = $this->tsqm->runTask($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(0, $task->getRetried());

        // Two failed retries
        for ($i = 0; $i < 2; $i++) {
            $task = $this->tsqm->getTask($task->getRootId());
            $task = $this->tsqm->runTask($task);
            $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError(), "step $i");
            $this->assertNull($task->getFinishedAt(), "step $i");
            $this->assertNull($task->getResult(), "step $i");
            $this->assertEquals($i + 1, $task->getRetried(), "step $i");
        }

        // Last success retry
        $task = $this->tsqm->getTask($task->getRootId());
        $task = $this->tsqm->runTask($task);
        $this->assertNull($task->getError());
        $this->assertNotNull($task->getFinishedAt());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $task->getResult());
        $this->assertEquals(3, $task->getRetried());
    }

    public function testTaskFailedAndFailedToRetry(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(2)
                    ->setMinInterval(0)
            );

        // Initial failed run
        $task = $this->tsqm->runTask($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(0, $task->getRetried());

        // One failed retry
        $task = $this->tsqm->getTask($task->getRootId());
        $task = $this->tsqm->runTask($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(1, $task->getRetried());

        // Last failed retry
        $task = $this->tsqm->getTask($task->getRootId());
        $task = $this->tsqm->runTask($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNotNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(2, $task->getRetried());
    }
}
