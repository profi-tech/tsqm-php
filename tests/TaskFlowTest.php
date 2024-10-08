<?php

namespace Tests;

use DateTime;
use Examples\Greeter\GreeterError;
use Examples\Greeter\SimpleGreet;
use Examples\Greeter\SimpleGreetWith3Fails;
use Examples\Greeter\SimpleGreetWithFail;
use Examples\Greeter\SimpleGreetWithTsqmFail;
use Tsqm\Errors\TsqmError;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class TaskFlowTest extends TestCase
{
    public function testTaskSuccess(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);

        $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
        $this->assertDateEquals($task->getResult()->getCreatedAt(), $now);
        $this->assertTrue($task->getResult()->getSent());
        $this->assertNull($task->getError());
    }

    public function testSuccessTaskCleanup(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);
        $this->assertTrue($task->isFinished());
        $this->assertNull($task->getError());

        $task = $this->tsqm->get($task->getId());
        $this->assertNull($task);
    }

    public function testTaskSuccessRerun(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $now = new DateTime();

        $task = $this->tsqm->run($task);

        $this->assertDateEquals($task->getFinishedAt(), $now);

        $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
        $this->assertTrue($task->getResult()->getSent());

        $this->assertNull($task->getError());
        $this->assertEquals(0, $task->getRetried());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->run($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);

            $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
            $this->assertTrue($task->getResult()->getSent());

            $this->assertNull($task->getError());
            $this->assertEquals(0, $task->getRetried());
        }
    }

    public function testTaskScheduled(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify("+10 second"));

        $task = $this->tsqm->run($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());
    }

    public function testTaskScheduledRerun(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify("+10 second"));

        $task = $this->tsqm->run($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->get($task->getRootId());
            $task = $this->tsqm->run($task);
            $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
            $this->assertNull($task->getFinishedAt());
            $this->assertNull($task->getResult());
            $this->assertNull($task->getError());
        }
    }

    public function testFailTaskScheduled(): void
    {
        $simpleGreetWithFail = $this->psrContainer->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setScheduledFor((new DateTime())->modify("+10 second"));

        $task = $this->tsqm->run($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());
    }


    public function testTaskFailed(): void
    {
        $simpleGreetWithFail = $this->psrContainer->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($this->getResult());
    }

    public function testFailedTaskCleanup(): void
    {
        $simpleGreetWithFail = $this->psrContainer->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);
        $this->assertTrue($task->isFinished());
        $this->assertNotNull($task->getError());

        $task = $this->tsqm->get($task->getId());
        $this->assertNull($task);
    }

    public function testTaskFailedRerun(): void
    {
        $simpleGreetWithFail = $this->psrContainer->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($this->getResult());
        $this->assertEquals(0, $task->getRetried());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->run($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);
            $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
            $this->assertNull($this->getResult());
            $this->assertEquals(0, $task->getRetried());
        }
    }

    public function testTaskFailedAndScheduled(): void
    {
        $simpleGreetWithFail = $this->psrContainer->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(1)
                    ->setMinInterval(10000)
            );

        $task = $this->tsqm->run($task);

        $scheduledFor = (new DateTime())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
    }

    public function testTaskFailedAndSuccesfullyRetried(): void
    {
        $simpleGreetWith3Fails = $this->psrContainer->get(SimpleGreetWith3Fails::class);
        $task = (new Task())
            ->setCallable($simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(3)
                    ->setMinInterval(0)
            );

        // Initial failed run
        $task = $this->tsqm->run($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(0, $task->getRetried());

        // Two failed retries
        for ($i = 0; $i < 2; $i++) {
            $task = $this->tsqm->get($task->getRootId());
            $task = $this->tsqm->run($task);
            $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError(), "step $i");
            $this->assertNull($task->getFinishedAt(), "step $i");
            $this->assertNull($task->getResult(), "step $i");
            $this->assertEquals($i + 1, $task->getRetried(), "step $i");
        }

        // Last success retry
        $task = $this->tsqm->get($task->getRootId());
        $task = $this->tsqm->run($task);
        $this->assertNull($task->getError());
        $this->assertNotNull($task->getFinishedAt());

        $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
        $this->assertTrue($task->getResult()->getSent());

        $this->assertEquals(3, $task->getRetried());
    }

    public function testTaskFailedAndFailedToRetry(): void
    {
        $simpleGreetWith3Fails = $this->psrContainer->get(SimpleGreetWith3Fails::class);
        $task = (new Task())
            ->setCallable($simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(2)
                    ->setMinInterval(0)
            );

        // Initial failed run
        $task = $this->tsqm->run($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(0, $task->getRetried());

        // One failed retry
        $task = $this->tsqm->get($task->getRootId());
        $task = $this->tsqm->run($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(1, $task->getRetried());

        // Last failed retry
        $task = $this->tsqm->get($task->getRootId());
        $task = $this->tsqm->run($task);
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNotNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(2, $task->getRetried());
    }

    public function testTsqmErrorDoesntAffectRetried(): void
    {
        $simpleGreetWithTsqmFail = $this->psrContainer->get(SimpleGreetWithTsqmFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithTsqmFail)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1));

        $this->expectException(TsqmError::class);
        $task = $this->tsqm->run($task);
        $this->expectException(TsqmError::class);
        $task = $this->tsqm->run($task);
        $this->expectException(TsqmError::class);
        $task = $this->tsqm->run($task);
        $this->expectException(TsqmError::class);
        $task = $this->tsqm->run($task);

        $this->assertEquals(0, $task->getRetried());

        $task = $this->tsqm->get($task->getId());
        $this->assertEquals(0, $task->getRetried());
    }
}
