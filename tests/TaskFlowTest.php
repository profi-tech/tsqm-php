<?php

namespace Tests;

use Carbon\CarbonImmutable;
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
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = CarbonImmutable::now();

        $this->assertDateEquals($task->getFinishedAt(), $now);

        $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
        $this->assertDateEquals($task->getResult()->getCreatedAt(), $now);
        $this->assertTrue($task->getResult()->getSent());
        $this->assertNull($task->getError());
    }

    public function testSuccessTaskCleanup(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
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
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $now = CarbonImmutable::now();

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
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((CarbonImmutable::now())->modify("+10 second"));

        $task = $this->tsqm->run($task);

        $scheduledFor = (CarbonImmutable::now())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());
    }

    public function testTaskScheduledRerun(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((CarbonImmutable::now())->modify("+10 second"));

        $task = $this->tsqm->run($task);

        $scheduledFor = (CarbonImmutable::now())->modify("+10 second");

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
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setScheduledFor((CarbonImmutable::now())->modify("+10 second"));

        $task = $this->tsqm->run($task);

        $scheduledFor = (CarbonImmutable::now())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());
    }


    public function testTaskFailed(): void
    {
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = CarbonImmutable::now();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertInstanceOf(GreeterError::class, $task->getError());
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($task->getResult());
    }

    public function testFailedTaskCleanup(): void
    {
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
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
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = CarbonImmutable::now();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($task->getResult());
        $this->assertEquals(0, $task->getRetried());

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->run($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);
            $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
            $this->assertNull($task->getResult());
            $this->assertEquals(0, $task->getRetried());
        }
    }

    public function testTaskFailedAndScheduled(): void
    {
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(1)
                    ->setMinInterval(10000)
            );

        $task = $this->tsqm->run($task);

        $scheduledFor = (CarbonImmutable::now())->modify("+10 second");

        $this->assertDateEquals($task->getScheduledFor(), $scheduledFor);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
    }

    public function testTaskFailedAndSuccesfullyRetried(): void
    {
        $simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
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
        $this->assertInstanceOf(GreeterError::class, $task->getError());
        $this->assertEquals(new GreeterError("Greet failed", 1700403919), $task->getError());
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertEquals(0, $task->getRetried());

        // Two failed retries (error deserialized from DB)
        for ($i = 0; $i < 2; $i++) {
            $task = $this->tsqm->get($task->getRootId());
            $task = $this->tsqm->run($task);
            $this->assertInstanceOf(GreeterError::class, $task->getError(), "step $i");
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
        $simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
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
        $simpleGreetWithTsqmFail = $this->container->get(SimpleGreetWithTsqmFail::class);
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
